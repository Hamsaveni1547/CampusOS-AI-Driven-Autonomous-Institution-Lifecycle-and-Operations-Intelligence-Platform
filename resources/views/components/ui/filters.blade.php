{{--
    Toolbar above a table: search + filter controls + active filter chips + clear.
    <x-ui.filters search="search" :active="['Status: Active' => 'status']">
        <x-ui.select wire:model.live="filters.status" :options="…" placeholder="All statuses" class="w-44" />
        <x-slot:actions><x-ui.button icon="download" variant="secondary">Export</x-ui.button></x-slot:actions>
    </x-ui.filters>
    active: [chip label => filter key]; chips call removeFilter('key'), "Clear all" calls resetFilters().
--}}
@props(['search' => null, 'searchPlaceholder' => null, 'active' => []])
<div {{ $attributes->class(['mb-4 space-y-3']) }}>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            @if ($search)
                <div class="w-full sm:w-72">
                    <x-ui.input icon="search" type="search" wire:model.live.debounce.300ms="{{ $search }}" :placeholder="$searchPlaceholder ?? __('Search…')" :aria-label="__('Search')" />
                </div>
            @endif
            {{ $slot }}
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @if ($active !== [])
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($active as $label => $key)
                <span class="inline-flex items-center gap-1 rounded-full border border-line bg-surface px-2.5 py-1 text-xs text-ink">
                    {{ $label }}
                    <button type="button" wire:click="removeFilter('{{ $key }}')" class="text-ink-subtle hover:text-ink" aria-label="{{ __('Remove filter :name', ['name' => $label]) }}">
                        <x-ui.icon name="x" class="size-3" />
                    </button>
                </span>
            @endforeach
            <button type="button" wire:click="resetFilters" class="text-xs font-medium text-primary hover:underline">{{ __('Clear all') }}</button>
        </div>
    @endif
</div>
