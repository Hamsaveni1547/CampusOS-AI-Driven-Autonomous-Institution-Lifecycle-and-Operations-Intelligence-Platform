@props(['icon' => null, 'href' => null, 'tone' => 'default', 'type' => 'button', 'shortcut' => null])
@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-sm transition focus:outline-none disabled:pointer-events-none disabled:opacity-50',
        'text-ink hover:bg-surface-muted focus:bg-surface-muted' => $tone !== 'danger',
        'text-danger hover:bg-danger-soft focus:bg-danger-soft' => $tone === 'danger',
    ]);
@endphp
@if ($href)
    <a href="{{ $href }}" role="menuitem" tabindex="-1" {{ $attributes->class($classes) }}>
@else
    <button type="{{ $type }}" role="menuitem" tabindex="-1" {{ $attributes->class($classes) }}>
@endif
    @if ($icon)
        <x-ui.icon :name="$icon" @class(['size-4', 'text-ink-muted' => $tone !== 'danger'])/>
    @endif
    <span class="flex min-w-0 flex-1 items-center gap-2">{{ $slot }}</span>
    @if ($shortcut)
        <kbd class="font-sans text-xs text-ink-subtle">{{ $shortcut }}</kbd>
    @endif
@if ($href)
    </a>
@else
    </button>
@endif
