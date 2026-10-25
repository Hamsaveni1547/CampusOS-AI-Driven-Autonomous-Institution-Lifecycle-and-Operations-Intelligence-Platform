{{--
    Auto-growing textarea with optional counter.
    <x-ui.textarea label="Description" wire:model="description" rows="3" maxlength="2000" />
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'rows' => 3, 'optional' => false])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
    $error ??= $key !== null ? $errors->first($key) : null;
    $max = $attributes->get('maxlength');
@endphp
<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$attributes->has('required')" :optional="$optional" class="{{ $attributes->get('class') }}"
    x-data="autoTextarea({ max: {{ $max ? (int) $max : 'null' }} })">
    <textarea
        x-ref="area"
        x-on:input="resize()"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->except('class')->class(['cos-control min-h-20 resize-none', 'cos-control-invalid' => $error]) }}
    >{{ $slot }}</textarea>
    @if ($max)
        <p class="mt-1 text-right text-xs text-ink-subtle tabular" aria-live="polite"><span x-text="count"></span> / {{ $max }}</p>
    @endif
</x-ui.field>
