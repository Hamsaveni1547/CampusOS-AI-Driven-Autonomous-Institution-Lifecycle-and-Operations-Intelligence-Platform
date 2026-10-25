{{--
    <x-ui.checkbox wire:model="remember" label="Remember this device" description="Skip the code here for 30 days." />
--}}
@props(['label' => null, 'description' => null, 'name' => null, 'error' => null])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
    $error ??= $key !== null ? $errors->first($key) : null;
@endphp
<div {{ $attributes->only('class')->class(['flex items-start gap-3']) }}>
    <input type="checkbox" id="{{ $id }}" @if ($name) name="{{ $name }}" @endif
        {{ $attributes->except('class')->class(['mt-0.5 size-4 shrink-0 rounded-[4px] border-line-strong bg-surface text-primary transition focus:ring-2 focus:ring-primary/30 focus:ring-offset-0 disabled:opacity-50 dark:bg-surface-muted']) }}>
    @if ($label || $description)
        <div class="min-w-0 text-sm">
            @if ($label)<label for="{{ $id }}" class="font-medium text-ink">{{ $label }}</label>@endif
            @if ($description)<p class="text-ink-muted">{{ $description }}</p>@endif
            @if ($error)<p class="mt-1 text-xs text-danger">{{ $error }}</p>@endif
        </div>
    @endif
</div>
