<?php

namespace App\Api;

use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ApiUnreachable;
use App\Api\Resources\AuthApi;
use App\Api\Resources\Endpoint;
use App\Api\Resources\MeApi;
use App\Auth\SessionAuth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
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
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = [], bool $memoize = false): ApiResponse
    {
        $key = $path.'?'.http_build_query($query);

        if ($memoize && isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $response = $this->send('GET', $path, ['query' => $query]);

        if ($memoize) {
            $this->memo[$key] = $response;
        }

        return $response;
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

    public function forgetMemo(): void
    {
        $this->memo = [];
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
        }

        return $this->handle($response, $method, $path);
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) config('campusos.api.url'))
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
