<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    protected const API = 'http://api.test/api/v1/';

    protected function setUp(): void
    {
        parent::setUp();

        // No built assets are needed to render pages in tests.
        Vite::useCspNonce();
        $this->withoutVite();

        // Any API call a test did not fake is a bug in the test.
        Http::preventStrayRequests();
    }

    /**
     * A realistic /me payload (overridable).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function me(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => '01JBXUSER000000000000000001',
            'name' => 'Anita Rao',
            'email' => 'anita@example.edu',
            'mobile' => null,
            'username' => null,
            'status' => 'active',
            'is_platform_admin' => false,
            'email_verified' => true,
            'mobile_verified' => false,
            'locale' => 'en',
            'timezone' => null,
            'avatar_file_id' => null,
            'mfa' => ['enabled' => true, 'required' => false, 'methods' => ['totp']],
            'pending_requirement' => null,
            'institution' => ['id' => '01JBXINST00000000000000001', 'code' => 'RVCE', 'name' => 'RV College of Engineering', 'short_name' => 'RVCE', 'type' => 'college', 'status' => 'active', 'timezone' => 'Asia/Kolkata', 'logo_file_id' => null],
            'institutions' => [
                ['id' => '01JBXINST00000000000000001', 'code' => 'RVCE', 'name' => 'RV College of Engineering', 'short_name' => 'RVCE', 'type' => 'college', 'status' => 'active', 'timezone' => 'Asia/Kolkata', 'logo_file_id' => null],
            ],
            'roles' => ['faculty'],
            'permissions' => ['hr.employees.view'],
        ], $overrides);
    }

    /**
     * Put a signed-in session in place (as after a successful login).
     *
     * @param  array<string, mixed>  $me
     */
    protected function signIn(array $me = [], string $token = '7|cos_testtoken'): static
    {
        $me = $this->me($me);

        return $this->withSession([
            'campus.token' => $token,
            'campus.token_expires_at' => now()->addHours(8)->toIso8601String(),
            'campus.me' => $me,
            'campus.me_refreshed_at' => now()->getTimestamp(),
            'campus.institution' => $me['institution']['id'] ?? null,
        ]);
    }

    /**
     * API error body in the backend's envelope.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function apiError(string $message, string $code, array $extra = []): array
    {
        return array_merge(['message' => $message, 'code' => $code, 'trace_id' => 'trace-from-api-123'], $extra);
    }
}
