{{--
    <x-ui.alert tone="warning" title="Two-step verification is off">Turn it on to protect your account.</x-ui.alert>
    tone: info | success | warning | danger       dismissible: adds a close button
    Slot "actions" for buttons on the right.
--}}
@props(['tone' => 'info', 'title' => null, 'icon' => null, 'dismissible' => false])
@php
    $styles = [
        'info' => ['bg-info-soft border-info/25', 'text-info', 'info'],
        'success' => ['bg-success-soft border-success/25', 'text-success', 'circle-check'],
        'warning' => ['bg-warning-soft border-warning/30', 'text-warning', 'triangle-alert'],
        'danger' => ['bg-danger-soft border-danger/25', 'text-danger', 'circle-alert'],
    ];
    [$box, $iconColour, $defaultIcon] = $styles[$tone] ?? $styles['info'];
@endphp
<div @if ($dismissible) x-data="{ shown: true }" x-show="shown" x-transition.opacity @endif
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
    {{ $attributes->class(['flex gap-3 rounded-lg border p-3.5', $box]) }}>
    <x-ui.icon :name="$icon ?? $defaultIcon" class="mt-0.5 size-4 {{ $iconColour }}" />
    <div class="min-w-0 flex-1 text-sm">
        @if ($title)
            <p class="font-medium text-ink">{{ $title }}</p>
        @endif
        @if (trim((string) $slot) !== '')
            <div @class(['text-ink-muted', 'mt-0.5' => $title])>{{ $slot }}</div>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 items-start gap-2">{{ $actions }}</div>
    @endisset
    @if ($dismissible)
        <button type="button" x-on:click="shown = false" class="-m-1 rounded-md p-1 text-ink-subtle hover:text-ink" aria-label="{{ __('Dismiss') }}">
            <x-ui.icon name="x" class="size-4" />
        </button>
    @endif
</div>
