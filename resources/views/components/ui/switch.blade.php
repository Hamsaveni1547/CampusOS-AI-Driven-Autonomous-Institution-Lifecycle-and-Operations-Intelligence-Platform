{{--
    Toggle switch bound to a boolean.
    <x-ui.switch wire:model.live="settings.notify_new_device_sign_in" label="Alert on new devices" description="Email users when…" />
--}}
@props(['label' => null, 'description' => null, 'name' => null])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
@endphp
<div {{ $attributes->only('class')->class(['flex items-start justify-between gap-4']) }}>
    @if ($label || $description)
        <div class="min-w-0 text-sm">
            @if ($label)<label for="{{ $id }}" class="font-medium text-ink">{{ $label }}</label>@endif
            @if ($description)<p class="mt-0.5 text-ink-muted">{{ $description }}</p>@endif
        </div>
    @endif
    <label class="relative inline-flex shrink-0 cursor-pointer items-center">
        <input type="checkbox" role="switch" id="{{ $id }}" @if ($name) name="{{ $name }}" @endif class="peer sr-only" {{ $attributes->except('class') }}>
        <span class="h-6 w-11 rounded-full bg-line-strong transition peer-checked:bg-primary peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary peer-disabled:opacity-50"></span>
        <span class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span>
    </label>
</div>
