{{--
    <x-ui.input label="Email" type="email" wire:model="form.email" required />
    <x-ui.input label="Search" icon="search" wire:model.live.debounce.300ms="search" />
    <x-ui.input label="Phone" prefix="+91" />
    Props: label, hint, error (auto from $errors), icon (leading), prefix/suffix text, optional.
    Slot "trailing" for an element inside the right edge (e.g. a button).
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'type' => 'text', 'icon' => null, 'prefix' => null, 'suffix' => null, 'optional' => false])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
    $error ??= $key !== null ? $errors->first($key) : null;
    $required = $attributes->has('required');
    $describedBy = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp
<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required" :optional="$optional" class="{{ $attributes->get('class') }}">
    <div class="relative flex items-stretch">
        @if ($prefix)
            <span class="inline-flex items-center rounded-l-sm border border-r-0 border-line-strong bg-surface-muted px-3 text-sm text-ink-muted">{{ $prefix }}</span>
        @endif
        <div class="relative flex-1">
            @if ($icon)
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-subtle">
                    <x-ui.icon :name="$icon" class="size-4" />
                </span>
            @endif
            <input
                type="{{ $type }}"
                id="{{ $id }}"
                @if ($name) name="{{ $name }}" @endif
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $attributes->except('class')->class([
                    'cos-control',
                    'cos-control-invalid' => $error,
                    'pl-9' => $icon,
                    'pr-10' => isset($trailing),
                    'rounded-l-none' => $prefix,
                    'rounded-r-none' => $suffix,
                    'tabular' => in_array($type, ['number', 'tel'], true),
                ]) }}
            >
            @isset($trailing)
                <div class="absolute inset-y-0 right-0 flex items-center pr-1.5">{{ $trailing }}</div>
            @endisset
        </div>
        @if ($suffix)
            <span class="inline-flex items-center rounded-r-sm border border-l-0 border-line-strong bg-surface-muted px-3 text-sm text-ink-muted">{{ $suffix }}</span>
        @endif
    </div>
</x-ui.field>
