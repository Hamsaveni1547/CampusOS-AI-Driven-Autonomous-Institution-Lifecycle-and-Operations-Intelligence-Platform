{{-- Ctrl/⌘+K quick navigation (rendered once by the app layout). --}}
@props(['items' => []])
<div
    x-data="commandPalette({ items: @js($items) })"
    x-on:keydown.window="shortcut($event)"
    x-on:open-command-palette.window="show()"
    x-on:keydown.escape.window="open && close()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-60 overflow-y-auto p-4 pt-[12vh]"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Quick navigation') }}"
>
    <div class="fixed inset-0 bg-slate-950/50" x-on:click="close()" aria-hidden="true"></div>
    <div x-show="open" x-trap.inert="open" x-transition.opacity.duration.100ms
        class="relative mx-auto max-w-xl overflow-hidden rounded-xl border border-line bg-surface-raised shadow-lg">
        <div class="flex items-center gap-3 border-b border-line px-4">
            <x-ui.icon name="search" class="size-5 text-ink-subtle" />
            <input type="text" x-ref="search" x-model="query" x-on:input="active = 0"
                x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go(results[active])"
                class="h-12 w-full border-0 bg-transparent p-0 text-sm text-ink placeholder:text-ink-subtle focus:ring-0"
                placeholder="{{ __('Search pages…') }}" aria-label="{{ __('Search pages') }}" role="combobox" aria-expanded="true">
            <kbd class="rounded border border-line px-1.5 font-sans text-[11px] text-ink-subtle">Esc</kbd>
        </div>
        <ul class="max-h-80 overflow-y-auto p-2" role="listbox">
            <template x-for="(item, index) in results" :key="item.url">
                <li role="option" :aria-selected="active === index" x-on:click="go(item)" x-on:mouseenter="active = index"
                    class="flex cursor-pointer items-center justify-between gap-3 rounded-md px-3 py-2 text-sm"
                    :class="active === index ? 'bg-primary-soft text-primary-ink' : 'text-ink'">
                    <span x-text="item.label"></span>
                    <span class="text-xs text-ink-subtle" x-text="item.section"></span>
                </li>
            </template>
            <li x-show="results.length === 0" class="px-3 py-8 text-center text-sm text-ink-muted">{{ __('No pages match your search.') }}</li>
        </ul>
    </div>
</div>
