<?php

namespace Tests\Feature\Api;

use App\Api\CampusApi;
use App\Api\Exceptions\NotFound;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReadCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['campusos.api.url' => rtrim(self::API, '/'), 'cache.default' => 'array']);
        $this->signIn();
        $this->get('/_none'); // start the session so SessionAuth sees the token
        session()->put('campus.token', '7|cos_testtoken');
        session()->put('campus.institution', '01JBXINST00000000000000001');
    }

    private function api(): CampusApi
    {
        return app(CampusApi::class);
    }

    private function asLivewireUpdate(): void
    {
        request()->headers->set('X-Livewire', '1');
    }

    public function test_full_page_reads_always_go_to_the_api(): void
    {
        Http::fake([self::API.'departments*' => Http::response(['data' => []])]);

        $this->api()->get('departments');
        app()->forgetInstance(CampusApi::class);
        $this->api()->get('departments');

        Http::assertSentCount(2);
    }

    public function test_livewire_round_trip_reuses_recent_reads(): void
    {
        $this->asLivewireUpdate();
        Http::fake([self::API.'departments*' => Http::response(['data' => [['id' => 'd1']]])]);

        $first = $this->api()->get('departments', ['filter' => ['status' => 'active']]);
        app()->forgetInstance(CampusApi::class);
        $second = $this->api()->get('departments', ['filter' => ['status' => 'active']]);

        Http::assertSentCount(1);
        $this->assertSame($first->data(), $second->data());
    }

    public function test_lookups_are_cached_across_page_loads(): void
    {
        Http::fake([self::API.'roles*' => Http::response(['data' => [['id' => 'r1']]])]);

        $this->api()->endpoint('roles')->all();
        app()->forgetInstance(CampusApi::class);
        $this->api()->endpoint('roles')->all();

        Http::assertSentCount(1);
    }

    public function test_a_write_clears_the_read_cache(): void
    {
        $this->asLivewireUpdate();
        Http::fake([
            self::API.'departments' => fn (Request $request) => $request->method() === 'POST'
                ? Http::response(['data' => ['id' => 'd2']], 201)
                : Http::response(['data' => []]),
        ]);

        $this->api()->get('departments');
        $this->api()->post('departments', ['code' => 'CSE', 'name' => 'Computer Science']);
        app()->forgetInstance(CampusApi::class);
        $this->api()->get('departments');

        Http::assertSentCount(3);
    }

    public function test_cache_is_separate_per_session_token(): void
    {
        $this->asLivewireUpdate();
        Http::fake([self::API.'departments*' => Http::response(['data' => []])]);

        $this->api()->get('departments');
        session()->put('campus.token', '8|cos_othertoken');
        app()->forgetInstance(CampusApi::class);
        $this->api()->get('departments');

        Http::assertSentCount(2);
    }

    public function test_live_reads_skip_the_cache(): void
    {
        $this->asLivewireUpdate();
        Http::fake([self::API.'admin/health' => Http::response(['data' => ['status' => 'ok']])]);

        $this->api()->withoutCache(fn () => $this->api()->get('admin/health'));
        $this->api()->withoutCache(fn () => $this->api()->get('admin/health'));

        Http::assertSentCount(2);
    }

    public function test_cached_bodies_are_encrypted(): void
    {
        $this->asLivewireUpdate();
        Http::fake([self::API.'users/u1' => Http::response(['data' => ['email' => 'secret@example.edu']])]);

        $this->api()->get('users/u1');

        $store = Cache::store('array');
        $found = false;

        foreach ((fn () => $this->storage)->call($store->getStore()) as $entry) {
            $value = $entry['value'] ?? null;
            $raw = is_string($value) ? $value : serialize($value);
            $this->assertStringNotContainsString('secret@example.edu', $raw);
            $found = $found || str_contains($raw, 'body');
        }

        $this->assertTrue($found);
    }

    public function test_get_many_runs_calls_together_and_keeps_names(): void
    {
        Http::fake([
            self::API.'users/u1/security-events*' => Http::response(['data' => [['id' => 'e1']], 'meta' => ['next_cursor' => null]]),
            self::API.'users/u1' => Http::response(['data' => ['id' => 'u1', 'name' => 'Anita']]),
        ]);

        $results = $this->api()->getMany(['user' => 'users/u1', 'events' => ['users/u1/security-events', ['per_page' => 10]]]);

        $this->assertSame(['user', 'events'], array_keys($results));
        $this->assertSame('Anita', $results['user']->data('name'));
        $this->assertSame('e1', $results['events']->page()->items[0]['id']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'per_page=10') && $request->hasHeader('Authorization', 'Bearer 7|cos_testtoken'));
    }

    public function test_get_many_turns_api_errors_into_exceptions(): void
    {
        Http::fake([
            self::API.'a' => Http::response(['data' => []]),
            self::API.'b' => Http::response($this->apiError('Not found', 'NOT_FOUND'), 404),
        ]);

        $this->expectException(NotFound::class);

        $this->api()->getMany(['a' => 'a', 'b' => 'b']);
    }
}
