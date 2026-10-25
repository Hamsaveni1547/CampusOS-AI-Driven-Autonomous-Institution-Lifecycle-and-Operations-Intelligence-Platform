<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Inline SVG icons from the lucide-static npm package (ISC licence), so every Lucide
 * icon name works (https://lucide.dev/icons). Rendered inline so they inherit
 * `currentColor` and need no extra requests.
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
                throw new InvalidArgumentException("Icon [{$name}] not found. Use a name from https://lucide.dev/icons and make sure `npm install` has run.");
            }

            return self::$cache[$name] = '<circle cx="12" cy="12" r="9" />';
        }

        $svg = (string) file_get_contents($path);
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svg, $matches);

        return self::$cache[$name] = trim(preg_replace('/\s+/', ' ', $matches[1] ?? '') ?? '');
    }

    private static function path(string $name): string
    {
        return rtrim((string) config('campusos.icons_path'), '/\\').DIRECTORY_SEPARATOR.basename($name).'.svg';
    }
}
