{{-- <x-ui.tooltip text="Copy id"><x-ui.button square icon="copy" variant="ghost" label="Copy id" /></x-ui.tooltip> --}}
@props(['text', 'position' => 'top'])
<span x-data="{ shown: false }" x-on:mouseenter="shown = true" x-on:mouseleave="shown = false" x-on:focusin="shown = true" x-on:focusout="shown = false"
    {{ $attributes->class(['relative inline-flex']) }}>
    {{ $slot }}
    <span x-show="shown" x-cloak x-transition.opacity.duration.100ms role="tooltip"
        @class([
            'pointer-events-none absolute left-1/2 z-30 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-lg dark:bg-slate-700',
            'bottom-full mb-1.5' => $position === 'top',
            'top-full mt-1.5' => $position === 'bottom',
        ])>{{ $text }}</span>
</span>
