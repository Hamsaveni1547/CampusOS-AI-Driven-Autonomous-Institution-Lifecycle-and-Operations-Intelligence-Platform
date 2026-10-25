{{--
    Page navigation for an App\Api\Page. Calls Livewire gotoPage(n) / setPerPage(n) (WithTable concern).
    <x-ui.pagination :page="$page" />
--}}
@props(['page', 'perPageOptions' => [10, 25, 50, 100]])
@php
    /** @var \App\Api\Page $page */
    $current = $page->currentPage;
    $last = max($page->lastPage, 1);
    $window = collect(range(max(1, $current - 2), min($last, $current + 2)));
@endphp
<nav {{ $attributes->class(['flex flex-col items-center justify-between gap-3 text-sm sm:flex-row']) }} aria-label="{{ __('Page navigation') }}">
    <div class="flex items-center gap-3 text-ink-muted">
        @if ($page->total !== null)
            <span class="tabular">{{ __(':from–:to of :total', ['from' => \App\Support\Format::number($page->from()), 'to' => \App\Support\Format::number($page->to()), 'total' => \App\Support\Format::number($page->total)]) }}</span>
        @endif
        <label class="hidden items-center gap-1.5 sm:flex">
            <span>{{ __('Rows') }}</span>
            <select wire:change="setPerPage($event.target.value)" class="cos-control h-8 w-auto py-0 pr-8 text-xs">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($option === $page->perPage)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
    </div>

    @if ($last > 1)
        <div class="flex items-center gap-1">
            <x-ui.button variant="ghost" size="sm" square icon="chevron-left" :label="__('Previous page')" :disabled="$current <= 1" wire:click="gotoPage({{ $current - 1 }})" />
            @if ($window->first() > 1)
                <x-ui.button variant="ghost" size="sm" wire:click="gotoPage(1)">1</x-ui.button>
                @if ($window->first() > 2)<span class="px-1 text-ink-subtle">…</span>@endif
            @endif
            @foreach ($window as $number)
                <x-ui.button :variant="$number === $current ? 'soft' : 'ghost'" size="sm" class="tabular min-w-8" wire:click="gotoPage({{ $number }})" :aria-current="$number === $current ? 'page' : null">{{ $number }}</x-ui.button>
            @endforeach
            @if ($window->last() < $last)
                @if ($window->last() < $last - 1)<span class="px-1 text-ink-subtle">…</span>@endif
                <x-ui.button variant="ghost" size="sm" class="tabular" wire:click="gotoPage({{ $last }})">{{ $last }}</x-ui.button>
            @endif
            <x-ui.button variant="ghost" size="sm" square icon="chevron-right" :label="__('Next page')" :disabled="$current >= $last" wire:click="gotoPage({{ $current + 1 }})" />
        </div>
    @endif
</nav>
