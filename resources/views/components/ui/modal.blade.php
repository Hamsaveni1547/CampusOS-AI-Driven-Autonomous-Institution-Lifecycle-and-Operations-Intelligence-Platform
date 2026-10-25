{{--
    <x-ui.modal name="edit-room" title="Edit room" description="…" size="md">
        …form…
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="close()">Cancel</x-ui.button>
            <x-ui.button wire:click="save" loading="save">Save</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    Open: $dispatch('open-dialog', 'edit-room') (Alpine) / $this->dispatch('open-dialog', 'edit-room') (Livewire).
    size: sm | md | lg | xl
--}}
@props(['name', 'title' => null, 'description' => null, 'size' => 'md', 'open' => false, 'dismissible' => true])
@php
    $sizes = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-lg', 'lg' => 'sm:max-w-2xl', 'xl' => 'sm:max-w-4xl'];
@endphp
<div
    x-data="dialog({ name: @js($name), open: @js((bool) $open), dismissible: @js((bool) $dismissible) })"
    x-on:open-dialog.window="handleOpen($event)"
    x-on:close-dialog.window="handleClose($event)"
    x-on:keydown.escape.window="open && dismiss()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-60 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $name }}-title" @endif
    {{ $attributes->except('class') }}
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-950/50 backdrop-blur-[2px]" x-on:click="dismiss()" aria-hidden="true"></div>

    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
        <div
            x-show="open"
            x-trap.noscroll.inert="open"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            @class(['relative w-full overflow-hidden rounded-t-xl border border-line bg-surface shadow-lg sm:rounded-xl', $sizes[$size] ?? $sizes['md'], $attributes->get('class')])
        >
            @if ($title)
                <div class="flex items-start justify-between gap-4 px-6 pb-2 pt-5">
                    <div>
                        <h2 id="{{ $name }}-title" class="text-base font-semibold text-ink">{{ $title }}</h2>
                        @if ($description)
                            <p class="mt-1 text-sm text-ink-muted">{{ $description }}</p>
                        @endif
                    </div>
                    @if ($dismissible)
                        <button type="button" x-on:click="close()" class="-m-1.5 rounded-md p-1.5 text-ink-subtle hover:bg-surface-muted hover:text-ink" aria-label="{{ __('Close') }}">
                            <x-ui.icon name="x" class="size-5" />
                        </button>
                    @endif
                </div>
            @endif

            <div class="max-h-[70vh] overflow-y-auto px-6 py-4">{{ $slot }}</div>

            @isset($footer)
                <div class="flex flex-col-reverse gap-2 border-t border-line bg-surface-muted/40 px-6 py-3.5 sm:flex-row sm:justify-end">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
