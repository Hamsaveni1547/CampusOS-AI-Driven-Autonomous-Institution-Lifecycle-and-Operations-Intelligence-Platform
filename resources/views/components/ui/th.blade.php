{{--
    Header cell. Sortable headers call the Livewire method sortBy('<column>') (WithTable concern).
    <x-ui.th sortable="name" :sort="$sort">Name</x-ui.th>   ($sort like "name" or "-name")
--}}
@props(['sortable' => null, 'sort' => null, 'align' => 'left'])
@php
    $direction = null;
    if ($sortable !== null && is_string($sort)) {
        $direction = $sort === $sortable ? 'asc' : ($sort === '-'.$sortable ? 'desc' : null);
    }
@endphp
<th scope="col"
    @if ($sortable) aria-sort="{{ $direction === 'asc' ? 'ascending' : ($direction === 'desc' ? 'descending' : 'none') }}" @endif
    {{ $attributes->class(['whitespace-nowrap px-4 py-2.5 font-medium', 'text-right' => $align === 'right', 'text-center' => $align === 'center']) }}>
    @if ($sortable)
        <button type="button" wire:click="sortBy('{{ $sortable }}')" class="group inline-flex items-center gap-1 rounded hover:text-ink">
            {{ $slot }}
            <span @class(['text-ink-subtle', 'opacity-0 group-hover:opacity-100' => $direction === null, 'text-ink' => $direction !== null])>
                <x-ui.icon :name="$direction === 'desc' ? 'arrow-down' : ($direction === 'asc' ? 'arrow-up' : 'arrow-up-down')" class="size-3.5" />
            </span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
