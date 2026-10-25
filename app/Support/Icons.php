<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Inline SVG icons from resources/icons (Lucide, ISC licence). Rendered inline so
 * they inherit `currentColor` and need no extra requests.
 */
final class Icons
{
    /**
     * @var array<string, string>
     */
    private static array $cache = [];

    public static function svg(string $name, string $class = 'size-4', ?string $label = null, float $strokeWidth = 2): string
    {
        $inner = self::inner($name);

        $a11y = $label === null
            ? 'aria-hidden="true" focusable="false"'
            : 'role="img" aria-label="'.e($label).'"';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            .'stroke-width="'.$strokeWidth.'" stroke-linecap="round" stroke-linejoin="round" class="'.e($class).' shrink-0" '
            .$a11y.'>'.$inner.'</svg>';
    }

    public static function exists(string $name): bool
    {
        return is_file(self::path($name));
    }

    private static function inner(string $name): string
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $path = self::path($name);

        if (! is_file($path)) {
            if (app()->environment('local', 'testing')) {
                throw new InvalidArgumentException("Icon [{$name}] not found. Add it to scripts/sync-icons.mjs and run `npm run icons`.");
            }

            return self::$cache[$name] = '<circle cx="12" cy="12" r="9" />';
        }

        $svg = (string) file_get_contents($path);
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svg, $matches);

        return self::$cache[$name] = trim(preg_replace('/\s+/', ' ', $matches[1] ?? '') ?? '');
    }

    private static function path(string $name): string
    {
        return resource_path('icons/'.basename($name).'.svg');
    }
}
