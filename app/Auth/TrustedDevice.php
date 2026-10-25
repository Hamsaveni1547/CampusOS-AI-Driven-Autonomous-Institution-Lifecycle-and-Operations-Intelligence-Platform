<?php

namespace App\Auth;

use Illuminate\Support\Facades\Cookie;

/**
 * The "remember this device" token from the API, kept in an encrypted, HTTP-only
 * cookie so the next sign-in on this browser can skip two-step verification.
 */
final class TrustedDevice
{
    public static function token(): ?string
    {
        $value = request()->cookie((string) config('campusos.trusted_device_cookie'));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function remember(string $token): void
    {
        Cookie::queue(Cookie::make(
            (string) config('campusos.trusted_device_cookie'),
            $token,
            (int) config('campusos.trusted_device_days') * 24 * 60,
            secure: request()->isSecure(),
            httpOnly: true,
            sameSite: 'lax',
        ));
    }

    public static function forget(): void
    {
        Cookie::queue(Cookie::forget((string) config('campusos.trusted_device_cookie')));
    }
}
