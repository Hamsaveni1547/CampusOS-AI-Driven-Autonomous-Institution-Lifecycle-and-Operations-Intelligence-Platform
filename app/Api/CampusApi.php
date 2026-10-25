<?php

namespace App\Api;

use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ApiUnreachable;
use App\Api\Resources\AuthApi;
use App\Api\Resources\Endpoint;
use App\Api\Resources\MeApi;
use App\Auth\SessionAuth;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The only way the frontend talks to campusos-backend.
 *
 * Adds token, institution, trace id and the browser's IP/user agent to every call,
 * and turns error answers into typed exceptions (see App\Api\Exceptions).
 */
class CampusApi
{
    /**
     * Per-request memo for repeated GETs (e.g. the same lookup used twice on a page).
     *
     * @var array<string, ApiResponse>
     */
    private array $memo = [];

    private bool $cacheDisabled = false;

    public function __construct(
        private SessionAuth $auth,
        private RequestTrace $trace,
    ) {}

    // ----- endpoint groups -------------------------------------------------

    public function auth(): AuthApi
    {
        return new AuthApi($this);
    }

    public function me(): MeApi
    {
        return new MeApi($this);
    }

    /**
     * Plain REST collection, e.g. `$api->endpoint('departments')->list([...])`.
     */
    public function endpoint(string $path): Endpoint
    {
        return new Endpoint($this, $path);
    }

    // ----- HTTP verbs ------------------------------------------------------

    /**
     * GET with two speed-ups:
     *  - $memoize: the same call twice in one request hits the API once.
     *  - Short read cache (campusos.api.read_cache_seconds) shared across requests of the
     *    same signed-in session, used for lookups ($memoize) and for every read during a
     *    Livewire round trip (opening a modal, sorting) so the page is not re-fetched.
     *    A full page load always reads fresh data, and any write by this session clears it.
     *
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = [], bool $memoize = false): ApiResponse
    {
        $path = ltrim($path, '/');
        $key = $path.'?'.http_build_query($this->clean($query));

        if ($memoize && isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $cacheKey = $this->readCacheKey($key, $memoize);
        $cached = $cacheKey !== null ? Cache::get($cacheKey) : null;

        if (is_array($cached)) {
            $response = $this->fromCache($cached);
        } else {
            $response = $this->send('GET', $path, ['query' => $query]);

            if ($cacheKey !== null) {
                Cache::put($cacheKey, $this->toCache($response->raw()), $this->readCacheSeconds());
            }
        }

        if ($memoize) {
            $this->memo[$key] = $response;
        }

        return $response;
    }

    /**
     * Several GETs at once (in parallel), e.g. a page and its side panels.
     *
     * @param  array<string, string|array{0: string, 1?: array<string, mixed>}>  $calls  name => path, or name => [path, query]
     * @return array<string, ApiResponse>
     */
    public function getMany(array $calls): array
    {
        $results = [];
        $pending = [];

        foreach ($calls as $name => $call) {
            [$path, $query] = is_array($call) ? [ltrim($call[0], '/'), $call[1] ?? []] : [ltrim($call, '/'), []];
            $cacheKey = $this->readCacheKey($path.'?'.http_build_query($this->clean($query)), false);
            $cached = $cacheKey !== null ? Cache::get($cacheKey) : null;

            if (is_array($cached)) {
                $results[$name] = $this->fromCache($cached);
            } else {
                $pending[$name] = [$path, $query, $cacheKey];
            }
        }

        if (count($pending) === 1) {
            $name = array_key_first($pending);
            $results[$name] = $this->get($pending[$name][0], $pending[$name][1]);
        } elseif ($pending !== []) {
            $responses = Http::pool(function (Pool $pool) use ($pending): array {
                $requests = [];

                foreach ($pending as $name => [$path, $query]) {
                    $requests[] = $this->configure($pool->as((string) $name))->get($path, $this->clean($query));
                }

                return $requests;
            });

            foreach ($pending as $name => [$path, , $cacheKey]) {
                $response = $responses[$name] ?? null;

                if (! $response instanceof Response) {
                    throw $this->unreachable($response instanceof ConnectionException ? $response : new ConnectionException('No response from the API.'));
                }

                $results[$name] = $this->handle($response, 'GET', $path);

                if ($cacheKey !== null) {
                    Cache::put($cacheKey, $this->toCache($response), $this->readCacheSeconds());
                }
            }
        }

        return array_replace(array_intersect_key($calls, $results), $results);
    }

    /**
     * Run reads that must be live (polling, progress) without the read cache.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withoutCache(callable $callback): mixed
    {
        $previous = $this->cacheDisabled;
        $this->cacheDisabled = true;

        try {
            return $callback();
        } finally {
            $this->cacheDisabled = $previous;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function post(string $path, array $data = []): ApiResponse
    {
        return $this->send('POST', $path, ['json' => $data]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function put(string $path, array $data = []): ApiResponse
    {
        return $this->send('PUT', $path, ['json' => $data]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function patch(string $path, array $data = []): ApiResponse
    {
        return $this->send('PATCH', $path, ['json' => $data]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function delete(string $path, array $data = []): ApiResponse
    {
        return $this->send('DELETE', $path, ['json' => $data]);
    }

    /**
     * Upload a file to POST /files and return the stored file record.
     *
     * @return array<string, mixed>
     */
    public function upload(UploadedFile $file, string $purpose): array
    {
        $stream = fopen($file->getRealPath(), 'r');

        try {
            $response = $this->request()
                ->attach('file', $stream, $file->getClientOriginalName())
                ->post('files', ['purpose' => $purpose]);
        } catch (ConnectionException $exception) {
            throw $this->unreachable($exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return (array) $this->handle($response, 'POST', 'files')->data();
    }

    /**
     * Raw GET for file downloads (CSV exports). Errors still become ApiExceptions.
     *
     * @param  array<string, mixed>  $query
     */
    public function download(string $path, array $query = []): Response
    {
        try {
            $response = $this->request()->accept('*/*')->get(ltrim($path, '/'), $this->clean($query));
        } catch (ConnectionException $exception) {
            throw $this->unreachable($exception);
        }

        return $this->handle($response, 'GET', $path)->raw();
    }

    /**
     * Forget everything read so far (this request and the session's read cache).
     */
    public function forgetMemo(): void
    {
        $this->memo = [];
        $this->bumpReadCache();
    }

    // ----- internals -------------------------------------------------------

    /**
     * @param  array<string, mixed>  $options
     */
    private function send(string $method, string $path, array $options): ApiResponse
    {
        $path = ltrim($path, '/');
        $request = $this->request();

        if ($method === 'GET') {
            $request = $request->retry(2, 200, fn ($exception) => $exception instanceof ConnectionException, throw: false);
        }

        try {
            $response = $request->send($method, $path, $this->stripEmptyQuery($options));
        } catch (ConnectionException $exception) {
            throw $this->unreachable($exception);
        }

        if ($method !== 'GET') {
            $this->memo = [];
            $this->bumpReadCache();
        }

        return $this->handle($response, $method, $path);
    }

    // ----- read cache ------------------------------------------------------

    private function readCacheSeconds(): int
    {
        return max(0, (int) config('campusos.api.read_cache_seconds', 20));
    }

    /**
     * Cache key for a read, or null when the read must go to the API.
     */
    private function readCacheKey(string $request, bool $memoize): ?string
    {
        $token = $this->auth->token();

        if ($this->cacheDisabled || $token === null || $this->readCacheSeconds() === 0) {
            return null;
        }

        if (! $memoize && ! request()->hasHeader('X-Livewire')) {
            return null;
        }

        $owner = hash('sha256', $token.'|'.$this->auth->institutionId());

        return 'campus_api:'.$owner.':'.$this->readCacheVersion($owner).':'.hash('sha256', $request.'|'.app()->getLocale());
    }

    private function readCacheVersion(string $owner): int
    {
        return (int) Cache::get('campus_api_version:'.$owner, 0);
    }

    private function bumpReadCache(): void
    {
        $token = $this->auth->token();

        if ($token === null) {
            return;
        }

        $owner = hash('sha256', $token.'|'.$this->auth->institutionId());
        Cache::forever('campus_api_version:'.$owner, $this->readCacheVersion($owner) + 1);
    }

    /**
     * @return array{status: int, headers: array<string, mixed>, body: string}
     */
    private function toCache(Response $response): array
    {
        return ['status' => $response->status(), 'headers' => ['Content-Type' => $response->header('Content-Type')], 'body' => Crypt::encryptString($response->body())];
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function fromCache(array $cached): ApiResponse
    {
        return new ApiResponse(new Response(new Psr7Response((int) $cached['status'], (array) $cached['headers'], Crypt::decryptString((string) $cached['body']))));
    }

    private function request(): PendingRequest
    {
        return $this->configure(Http::createPendingRequest());
    }

    private function configure(PendingRequest $request): PendingRequest
    {
        $request = $request->baseUrl((string) config('campusos.api.url'))
            ->timeout((int) config('campusos.api.timeout'))
            ->connectTimeout((int) config('campusos.api.connect_timeout'))
            ->acceptJson()
            ->withUserAgent('CampusOS-Web/'.app()->version())
            ->withHeaders(array_filter([
                'X-Trace-Id' => $this->trace->id(),
                'X-Client-IP' => request()->ip(),
                'X-Client-User-Agent' => Str::limit((string) request()->userAgent(), 500, ''),
                'X-Institution' => $this->auth->institutionId(),
                'Accept-Language' => app()->getLocale(),
            ], fn ($value) => $value !== null && $value !== ''));

        $token = $this->auth->token();

        return $token === null ? $request : $request->withToken($token);
    }

    private function handle(Response $response, string $method, string $path): ApiResponse
    {
        if ($response->successful()) {
            return new ApiResponse($response);
        }

        $exception = ApiException::fromResponse($response);

        if ($exception->status >= 500) {
            Log::error('CampusOS API error', ['method' => $method, 'path' => $path, 'status' => $exception->status, 'code' => $exception->errorCode, 'trace_id' => $exception->traceId]);
        }

        if ($exception->status === 401) {
            $this->auth->forget();
        }

        throw $exception;
    }

    private function unreachable(ConnectionException $exception): ApiUnreachable
    {
        Log::error('CampusOS API unreachable', ['error' => $exception->getMessage(), 'trace_id' => $this->trace->id()]);

        return ApiUnreachable::because($this->trace->id());
    }

    /**
     * Drop null/empty query values so filters left blank are not sent.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function stripEmptyQuery(array $options): array
    {
        if (isset($options['query']) && is_array($options['query'])) {
            $options['query'] = $this->clean($options['query']);
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function clean(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->clean($value);

                if ($values[$key] === []) {
                    unset($values[$key]);
                }
            } elseif ($value === null || $value === '') {
                unset($values[$key]);
            } elseif (is_bool($value)) {
                $values[$key] = $value ? '1' : '0';
            }
        }

        return $values;
    }
}
