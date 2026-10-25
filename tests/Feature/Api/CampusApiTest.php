<?php

namespace Tests\Feature\Api;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ApiUnreachable;
use App\Api\Exceptions\BusinessRuleFailed;
use App\Api\Exceptions\Conflict;
use App\Api\Exceptions\Forbidden;
use App\Api\Exceptions\NotFound;
use App\Api\Exceptions\RateLimited;
use App\Api\Exceptions\ServerError;
use App\Api\Exceptions\Unauthenticated;
use App\Api\Exceptions\ValidationFailed;
use App\Auth\SessionAuth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CampusApiTest extends TestCase
{
    public function test_requests_carry_token_institution_trace_and_client_details(): void
    {
        Http::fake([self::API.'departments*' => Http::response(['data' => [['id' => 'd1', 'name' => 'CSE']]])]);

        Route::middleware('web')->get('/_test/departments', fn (CampusApi $api) => response()->json($api->endpoint('departments')->all(['status' => 'active', 'search' => ''])));

        $this->signIn()
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeader('User-Agent', 'Firefox on Linux')
            ->get('/_test/departments')
            ->assertOk()
            ->assertJsonPath('0.name', 'CSE');

        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Bearer 7|cos_testtoken')
                && $request->hasHeader('X-Institution', '01JBXINST00000000000000001')
                && $request->hasHeader('X-Client-IP', '203.0.113.9')
                && $request->hasHeader('X-Client-User-Agent', 'Firefox on Linux')
                && $request->header('X-Trace-Id')[0] !== ''
                && str_contains($request->url(), 'filter%5Bstatus%5D=active')
                && ! str_contains($request->url(), 'search');
        });
    }

    public function test_error_statuses_become_typed_exceptions(): void
    {
        $cases = [
            [401, 'UNAUTHENTICATED', [], Unauthenticated::class],
            [403, 'FORBIDDEN', [], Forbidden::class],
            [404, 'NOT_FOUND', [], NotFound::class],
            [409, 'IN_USE', [], Conflict::class],
            [422, 'VALIDATION_FAILED', ['errors' => ['email' => ['Taken.']]], ValidationFailed::class],
            [422, 'HOD_NOT_ELIGIBLE', [], BusinessRuleFailed::class],
            [429, 'TOO_MANY_REQUESTS', ['context' => ['retry_after_seconds' => 30]], RateLimited::class],
            [500, 'SERVER_ERROR', [], ServerError::class],
        ];

        foreach ($cases as [$status, $code, $extra, $class]) {
            Http::swap(new Factory);
            Http::fake([self::API.'*' => Http::response($this->apiError('Problem', $code, $extra), $status)]);

            try {
                app(CampusApi::class)->post('departments', ['name' => 'x']);
                $this->fail("Expected {$class}");
            } catch (ApiException $exception) {
                $this->assertInstanceOf($class, $exception);
                $this->assertSame($code, $exception->errorCode);
                $this->assertSame('trace-from-api-123', $exception->traceId);

                if ($status === 429) {
                    $this->assertSame(30, $exception->context['retry_after_seconds'] ?? null);
                }
            }
        }

    }

    public function test_validation_errors_are_kept_per_field(): void
    {
        Http::fake([self::API.'*' => Http::response($this->apiError('Invalid', 'VALIDATION_FAILED', ['errors' => ['email' => ['The email is already used.']]]), 422)]);

        try {
            app(CampusApi::class)->post('users', []);
        } catch (ValidationFailed $exception) {
            $this->assertSame('The email is already used.', $exception->firstError('email'));
            $this->assertTrue($exception->is('VALIDATION_FAILED'));

            return;
        }

        $this->fail('Expected ValidationFailed');
    }

    public function test_unauthenticated_answer_signs_the_user_out(): void
    {
        Http::fake([self::API.'me' => Http::response($this->apiError('Expired', 'UNAUTHENTICATED'), 401)]);

        Route::middleware('web')->get('/_test/me', function (CampusApi $api, SessionAuth $auth) {
            try {
                $api->me()->profile();
            } catch (Unauthenticated) {
                return response()->json(['signed_in' => $auth->check()]);
            }

            return response()->json(['signed_in' => true]);
        });

        $this->signIn()->get('/_test/me')->assertJson(['signed_in' => false]);
    }

    public function test_connection_failures_become_unreachable_and_gets_are_retried(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('Connection refused');
        });

        $this->expectException(ApiUnreachable::class);

        try {
            app(CampusApi::class)->get('health');
        } finally {
            $this->assertSame(2, $attempts, 'GET is retried once');
        }
    }

    public function test_memoised_gets_hit_the_api_once(): void
    {
        Http::fake([self::API.'designations*' => Http::response(['data' => []])]);
        $api = app(CampusApi::class);

        $api->endpoint('designations')->all();
        $api->endpoint('designations')->all();

        Http::assertSentCount(1);
    }

    public function test_paginated_lists_expose_page_info(): void
    {
        Http::fake([self::API.'employees*' => Http::response([
            'data' => [['id' => 'e1'], ['id' => 'e2']],
            'meta' => ['current_page' => 2, 'last_page' => 5, 'per_page' => 2, 'total' => 9],
        ])]);

        $page = app(CampusApi::class)->endpoint('employees')->page(['status' => 'active'], ['page' => 2, 'per_page' => 2]);

        $this->assertCount(2, $page->items);
        $this->assertSame(3, $page->from());
        $this->assertSame(4, $page->to());
        $this->assertSame(9, $page->total);
    }
}
