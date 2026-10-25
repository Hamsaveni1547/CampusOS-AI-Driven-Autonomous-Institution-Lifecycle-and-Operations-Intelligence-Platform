<?php

namespace App\Api\Resources;

use App\Api\ApiResponse;
use App\Api\CampusApi;

/**
 * Public sign-in and account recovery endpoints (`/auth/*`).
 */
final class AuthApi
{
    public function __construct(private CampusApi $api) {}

    /**
     * @return array<string, mixed> either token data or `{ mfa_required: true, challenge_token, methods }`
     */
    public function login(string $login, string $password, string $deviceName, ?string $trustedDeviceToken): array
    {
        return (array) $this->api->post('auth/login', array_filter([
            'login' => $login,
            'password' => $password,
            'device_name' => $deviceName,
            'trusted_device_token' => $trustedDeviceToken,
        ], fn ($value) => $value !== null))->data();
    }

    /**
     * @return array{sent_to: string, expires_at: string}
     */
    public function sendMfaCode(string $challengeToken, string $method = 'email'): array
    {
        /** @var array{sent_to: string, expires_at: string} */
        return (array) $this->api->post('auth/mfa/send-code', ['challenge_token' => $challengeToken, 'method' => $method])->data();
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyMfa(string $challengeToken, string $method, string $code, bool $rememberDevice): array
    {
        return (array) $this->api->post('auth/mfa/verify', [
            'challenge_token' => $challengeToken,
            'method' => $method,
            'code' => $code,
            'remember_device' => $rememberDevice,
        ])->data();
    }

    public function logout(): ApiResponse
    {
        return $this->api->post('auth/logout');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): ApiResponse
    {
        return $this->api->post('auth/register', $data);
    }

    public function forgotPassword(string $email): ApiResponse
    {
        return $this->api->post('auth/forgot-password', ['email' => $email]);
    }

    public function resetPassword(string $email, string $token, string $password, string $confirmation, bool $isAccountSetup = false): ApiResponse
    {
        return $this->api->post($isAccountSetup ? 'auth/setup-account' : 'auth/reset-password', [
            'email' => $email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ]);
    }

    /**
     * @return array<string, mixed> updated /me
     */
    public function verifyEmail(string $code): array
    {
        return (array) $this->api->post('auth/verify-email', ['code' => $code])->data();
    }

    public function resendEmailVerification(): ApiResponse
    {
        return $this->api->post('auth/verify-email/resend');
    }
}
