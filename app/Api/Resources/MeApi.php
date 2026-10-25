<?php

namespace App\Api\Resources;

use App\Api\ApiResponse;
use App\Api\CampusApi;
use App\Api\Page;

/**
 * The signed-in user's own account (`/me/*`).
 */
final class MeApi
{
    public function __construct(private CampusApi $api) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        return (array) $this->api->get('me')->data();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        return (array) $this->api->put('me', $data)->data();
    }

    public function changePassword(string $current, string $password, string $confirmation): ApiResponse
    {
        return $this->api->put('me/password', [
            'current_password' => $current,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function institutions(): array
    {
        return array_values((array) $this->api->get('me/institutions')->data());
    }

    /**
     * @return array<string, mixed>
     */
    public function mfa(): array
    {
        return (array) $this->api->get('me/mfa')->data();
    }

    /**
     * @return array{secret: string, otpauth_uri: string, qr_svg: string}
     */
    public function startTotp(string $currentPassword): array
    {
        /** @var array{secret: string, otpauth_uri: string, qr_svg: string} */
        return (array) $this->api->post('me/mfa/totp', ['current_password' => $currentPassword])->data();
    }

    /**
     * @return array{method: array<string, mixed>, recovery_codes: array<int, string>|null}
     */
    public function confirmTotp(string $code): array
    {
        /** @var array{method: array<string, mixed>, recovery_codes: array<int, string>|null} */
        return (array) $this->api->post('me/mfa/totp/confirm', ['code' => $code])->data();
    }

    public function startEmailMfa(string $currentPassword): string
    {
        return (string) $this->api->post('me/mfa/email', ['current_password' => $currentPassword])->data('sent_to');
    }

    /**
     * @return array{method: array<string, mixed>, recovery_codes: array<int, string>|null}
     */
    public function confirmEmailMfa(string $code): array
    {
        /** @var array{method: array<string, mixed>, recovery_codes: array<int, string>|null} */
        return (array) $this->api->post('me/mfa/email/confirm', ['code' => $code])->data();
    }

    public function removeMfaMethod(string $methodId, string $currentPassword): ApiResponse
    {
        return $this->api->delete('me/mfa/'.$methodId, ['current_password' => $currentPassword]);
    }

    public function makeMfaPrimary(string $methodId): ApiResponse
    {
        return $this->api->post('me/mfa/'.$methodId.'/primary');
    }

    /**
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(string $currentPassword): array
    {
        return (array) $this->api->post('me/mfa/recovery-codes', ['current_password' => $currentPassword])->data('recovery_codes');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sessions(): array
    {
        return array_values((array) $this->api->get('me/sessions')->data());
    }

    public function revokeSession(string $id): ApiResponse
    {
        return $this->api->delete('me/sessions/'.$id);
    }

    public function revokeOtherSessions(): ApiResponse
    {
        return $this->api->delete('me/sessions');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function trustedDevices(): array
    {
        return array_values((array) $this->api->get('me/trusted-devices')->data());
    }

    public function revokeTrustedDevice(string $id): ApiResponse
    {
        return $this->api->delete('me/trusted-devices/'.$id);
    }

    public function securityEvents(?string $cursor = null): Page
    {
        return $this->api->get('me/security-events', ['cursor' => $cursor])->page();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function notifications(array $filters = [], ?string $cursor = null): Page
    {
        return $this->api->get('me/notifications', ['filter' => $filters, 'cursor' => $cursor])->page();
    }

    public function unreadNotifications(): int
    {
        return (int) $this->api->get('me/notifications/unread-count')->data('unread', 0);
    }

    public function markNotificationRead(string $id): ApiResponse
    {
        return $this->api->post('me/notifications/'.$id.'/read');
    }

    public function markAllNotificationsRead(): ApiResponse
    {
        return $this->api->post('me/notifications/read-all');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function notificationPreferences(): array
    {
        return array_values((array) $this->api->get('me/notification-preferences')->data());
    }

    /**
     * @param  array<string, array<string, bool>>  $preferences
     * @return array<int, array<string, mixed>>
     */
    public function updateNotificationPreferences(array $preferences): array
    {
        return array_values((array) $this->api->put('me/notification-preferences', ['preferences' => $preferences])->data());
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeProfile(): array
    {
        return (array) $this->api->get('me/employee-profile')->data();
    }
}
