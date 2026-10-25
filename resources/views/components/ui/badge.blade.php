{{--
    <x-ui.badge tone="success" icon="check">Active</x-ui.badge>
    tone: neutral | primary | success | warning | danger | info | muted       dot: show a coloured dot
--}}
@props(['tone' => 'neutral', 'icon' => null, 'dot' => false, 'size' => 'md'])
@php
    $tones = [
        'neutral' => 'bg-neutral-soft text-ink-muted ring-line',
        'muted' => 'bg-transparent text-ink-subtle ring-line',
        'primary' => 'bg-primary-soft text-primary-ink ring-primary/20',
        'success' => 'bg-success-soft text-success ring-success/20',
        'warning' => 'bg-warning-soft text-warning ring-warning/25',
        'danger' => 'bg-danger-soft text-danger ring-danger/20',
        'info' => 'bg-info-soft text-info ring-info/20',
    ];
    $dots = [
        'neutral' => 'bg-ink-subtle', 'muted' => 'bg-ink-subtle', 'primary' => 'bg-primary', 'success' => 'bg-success',
        'warning' => 'bg-warning', 'danger' => 'bg-danger', 'info' => 'bg-info',
    ];
@endphp
<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-sm font-medium whitespace-nowrap ring-1 ring-inset',
    $size === 'sm' ? 'px-1.5 py-0.5 text-[11px]' : 'px-2 py-0.5 text-xs',
    $tones[$tone] ?? $tones['neutral'],
]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dots[$tone] ?? $dots['neutral'] }}" aria-hidden="true"></span>
    @endif
    @if ($icon)
        <x-ui.icon :name="$icon" class="size-3" />
    @endif
    {{ $slot }}
</span>
