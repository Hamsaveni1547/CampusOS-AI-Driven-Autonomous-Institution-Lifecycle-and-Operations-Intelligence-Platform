<?php

namespace App\Support;

use App\Auth\SessionAuth;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Display formatting in the user's/institution's timezone and Indian number grouping.
 * Available in views as `Format::date($iso)` etc.
 */
final class Format
{
    public static function date(?string $value, string $format = 'd M Y'): string
    {
        return self::parse($value)?->format($format) ?? '—';
    }

    public static function dateTime(?string $value, string $format = 'd M Y, h:i A'): string
    {
        return self::parse($value)?->format($format) ?? '—';
    }

    public static function iso(?string $value): ?string
    {
        return self::parse($value)?->toIso8601String();
    }

    /**
     * 1234567 → "12,34,567" (Indian grouping).
     */
    public static function number(int|float|string|null $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = round((float) $value, $decimals);
        $negative = $number < 0;
        [$integer, $fraction] = array_pad(explode('.', number_format(abs($number), $decimals, '.', '')), 2, null);

        $last3 = substr($integer, -3);
        $rest = substr($integer, 0, -3);
        $grouped = $rest === '' ? $last3 : preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$last3;

        return ($negative ? '-' : '').$grouped.($fraction !== null && $decimals > 0 ? '.'.$fraction : '');
    }

    public static function bytes(int|float|null $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, $index === 0 ? 0 : 1).' '.$units[$index];
    }

    public static function label(?string $value): string
    {
        return $value === null || $value === '' ? '—' : ucfirst(str_replace('_', ' ', $value));
    }

    private static function parse(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $timezone = app(SessionAuth::class)->user()?->timezone() ?? 'Asia/Kolkata';

            return Carbon::parse($value)->setTimezone($timezone);
        } catch (Throwable) {
            return null;
        }
    }
}
