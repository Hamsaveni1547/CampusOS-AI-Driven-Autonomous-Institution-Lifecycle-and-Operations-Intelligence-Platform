{{--
    Side panel for quick create/edit over a list.
    <x-ui.drawer name="room-form" title="Add room" size="md">
        …fields…
        <x-slot:footer>…buttons…</x-slot:footer>
    </x-ui.drawer>
    Open/close like the modal: $dispatch('open-dialog', 'room-form').
--}}
@props(['name', 'title' => null, 'description' => null, 'size' => 'md', 'dismissible' => true])
@php
    $sizes = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-xl', 'xl' => 'sm:max-w-3xl'];
@endphp
<div
    x-data="dialog({ name: @js($name), dismissible: @js((bool) $dismissible) })"
    x-on:open-dialog.window="handleOpen($event)"
    x-on:close-dialog.window="handleClose($event)"
    x-on:keydown.escape.window="open && dismiss()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $name }}-title" @endif
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-950/40" x-on:click="dismiss()" aria-hidden="true"></div>

    <div
        x-show="open"
        x-trap.noscroll.inert="open"
        x-transition:enter="transform transition duration-200 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition duration-150 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @class(['fixed inset-y-0 right-0 flex w-full flex-col border-l border-line bg-surface shadow-lg', $sizes[$size] ?? $sizes['md']])
    >
        <div class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
            <div class="min-w-0">
                @if ($title)<h2 id="{{ $name }}-title" class="text-base font-semibold text-ink">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-sm text-ink-muted">{{ $description }}</p>@endif
            </div>
            <button type="button" x-on:click="close()" class="-m-1.5 rounded-md p-1.5 text-ink-subtle hover:bg-surface-muted hover:text-ink" aria-label="{{ __('Close') }}">
                <x-ui.icon name="x" class="size-5" />
            </button>
        </div>
        <div {{ $attributes->class(['flex-1 space-y-5 overflow-y-auto px-6 py-5']) }}>{{ $slot }}</div>
        @isset($footer)
            <div class="flex justify-end gap-2 border-t border-line bg-surface-muted/40 px-6 py-3.5">{{ $footer }}</div>
        @endisset
    </div>
</div>
