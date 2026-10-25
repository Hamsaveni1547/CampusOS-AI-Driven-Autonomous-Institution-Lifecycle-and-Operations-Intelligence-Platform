<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * Works out a form control's name/id/error key from its attributes,
 * so `<x-ui.input wire:model="form.email" />` finds the "form.email" error by itself.
 */
final class FieldName
{
    public static function key(ComponentAttributeBag $attributes, ?string $name = null): ?string
    {
        if ($name !== null && $name !== '') {
            return self::dotted($name);
        }

        $model = $attributes->wire('model')->value();

        if (is_string($model) && $model !== '') {
            return $model;
        }

        $attributeName = $attributes->get('name');

        return is_string($attributeName) ? self::dotted($attributeName) : null;
    }

    public static function id(ComponentAttributeBag $attributes, ?string $key): string
    {
        $id = $attributes->get('id');

        if (is_string($id) && $id !== '') {
            return $id;
        }

        return 'f-'.($key !== null ? Str::slug(str_replace('.', '-', $key)) : Str::random(8));
    }

    /**
     * "roles[]" / "address[city]" → "roles" / "address.city"
     */
    private static function dotted(string $name): string
    {
        return trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    }
}
