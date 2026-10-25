{{--
    <x-ui.stat label="Active staff" :value="128" icon="briefcase" hint="+4 this month" href="{{ route('staff.index') }}" />
--}}
@props(['label', 'value', 'icon' => null, 'hint' => null, 'tone' => 'primary', 'href' => null])
@php
    $iconTones = ['primary' => 'bg-primary-soft text-primary-ink', 'success' => 'bg-success-soft text-success', 'warning' => 'bg-warning-soft text-warning', 'danger' => 'bg-danger-soft text-danger', 'info' => 'bg-info-soft text-info'];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['flex items-start gap-4 rounded-lg border border-line bg-surface p-5 shadow-sm dark:shadow-none', 'transition hover:border-primary/40' => $href]) }}>
    @if ($icon)
        <span class="flex size-10 shrink-0 items-center justify-center rounded-md {{ $iconTones[$tone] ?? $iconTones['primary'] }}">
            <x-ui.icon :name="$icon" class="size-5" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="text-sm text-ink-muted">{{ $label }}</p>
        <p class="tabular mt-1 text-2xl font-semibold tracking-tight text-ink">{{ is_numeric($value) ? \App\Support\Format::number($value) : $value }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-ink-subtle">{{ $hint }}</p>
        @endif
    </div>
</{{ $tag }}>
