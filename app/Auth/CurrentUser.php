<?php

namespace App\Auth;

/**
 * Typed read-only view of the API's `/me` payload.
 */
final class CurrentUser
{
    /**
     * @param  array<string, mixed>  $me
     */
    public function __construct(private array $me) {}

    public function id(): string
    {
        return (string) ($this->me['id'] ?? '');
    }

    public function name(): string
    {
        return (string) ($this->me['name'] ?? '');
    }

    public function email(): string
    {
        return (string) ($this->me['email'] ?? '');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name())) ?: [];
        $letters = array_map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)), array_slice(array_filter($parts), 0, 2));

        return implode('', $letters) ?: '?';
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) ($this->me['is_platform_admin'] ?? false);
    }

    public function pendingRequirement(): ?string
    {
        $value = $this->me['pending_requirement'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * True when the user has the permission in the current institution.
     */
    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function canAny(array $permissions): bool
    {
        return array_intersect($permissions, $this->permissions()) !== [];
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return array_intersect($roles, $this->roles()) !== [];
    }

    /**
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return array_values(array_map('strval', (array) ($this->me['permissions'] ?? [])));
    }

    /**
     * @return array<int, string>
     */
    public function roles(): array
    {
        return array_values(array_map('strval', (array) ($this->me['roles'] ?? [])));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function institution(): ?array
    {
        $institution = $this->me['institution'] ?? null;

        return is_array($institution) ? $institution : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function institutions(): array
    {
        return array_values(array_filter((array) ($this->me['institutions'] ?? []), 'is_array'));
    }

    public function avatarFileId(): ?string
    {
        $value = $this->me['avatar_file_id'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function timezone(): string
    {
        $timezone = $this->me['timezone'] ?? null;
        $institutionTimezone = $this->institution()['timezone'] ?? null;

        return is_string($timezone) && $timezone !== '' ? $timezone : (is_string($institutionTimezone) ? $institutionTimezone : 'Asia/Kolkata');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->me, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->me;
    }
}
