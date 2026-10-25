{{--
    Click-to-open panel with arbitrary content (filters, quick info).
    <x-ui.popover width="w-80"><x-slot:trigger><x-ui.button variant="secondary" icon="filter">Filters</x-ui.button></x-slot:trigger>…</x-ui.popover>
--}}
@props(['align' => 'left', 'width' => 'w-72'])
<div x-data="{ open: false }" x-on:keydown.escape.prevent.stop="open = false" {{ $attributes->class(['relative inline-block']) }}>
    <div x-on:click="open = ! open" class="contents" :aria-expanded="open">{{ $trigger }}</div>
    <div x-show="open" x-cloak x-on:click.outside="open = false" x-transition.opacity.duration.100ms
        @class(['absolute z-30 mt-2 rounded-lg border border-line bg-surface-raised p-4 shadow-lg', $width, 'right-0' => $align === 'right', 'left-0' => $align === 'left'])>
        {{ $slot }}
    </div>
</div>
