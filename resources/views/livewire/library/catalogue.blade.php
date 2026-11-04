<div>
    <x-ui.page-header :title="__('Library')" :description="__('Search the catalogue by title, author, ISBN or accession number.')">
        @canapi('library.catalogue.manage')
            <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Add book') }}</x-ui.button></x-slot:actions>
        @endcanapi
    </x-ui.page-header>

    <div class="mb-4 w-96 max-w-full"><x-ui.input icon="search" wire:model.live.debounce.300ms="search" :placeholder="__('Title, author, ISBN or accession no.')" /></div>

    <x-ui.table loading="search,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Title') }}</x-ui.th>
            <x-ui.th>{{ __('Subject') }}</x-ui.th>
            <x-ui.th>{{ __('Available') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $b)
            <x-ui.tr wire:key="bk-{{ $b['id'] }}">
                <x-ui.td primary>
                    <button type="button" wire:click="show('{{ $b['id'] }}')" class="text-left font-medium text-ink hover:underline">{{ $b['title'] }}</button>
                    <span class="block text-xs text-ink-muted">{{ $b['authors'] }}@if ($b['edition']) · {{ $b['edition'] }}@endif @if ($b['call_number']) · {{ $b['call_number'] }}@endif</span>
                </x-ui.td>
                <x-ui.td :label="__('Subject')">{{ $b['subject'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Available')"><span @class(['font-semibold', 'text-success' => $b['available'] > 0, 'text-danger' => $b['available'] === 0])>{{ $b['available'] }}</span> / {{ $b['copies'] }}</x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="library" :title="__('No books found')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="book-form" :title="__('Add book')" size="lg">
        <form wire:submit="save" id="book-form-el" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('Title')" wire:model="book.title" required class="sm:col-span-2" />
            <x-ui.input :label="__('Authors')" wire:model="book.authors" required class="sm:col-span-2" />
            <x-ui.input :label="__('ISBN')" wire:model="book.isbn" optional />
            <x-ui.input :label="__('Publisher')" wire:model="book.publisher" optional />
            <x-ui.input :label="__('Edition')" wire:model="book.edition" optional />
            <x-ui.input type="number" :label="__('Year')" wire:model="book.published_year" optional />
            <x-ui.input :label="__('Subject')" wire:model="book.subject" optional />
            <x-ui.input :label="__('Call number')" wire:model="book.call_number" optional placeholder="005.74 SIL" />
            <x-ui.input :label="__('Accession numbers')" wire:model="book.accessions" optional :hint="__('One per copy, separated by commas.')" />
            <x-ui.input :label="__('Shelf')" wire:model="book.shelf" optional />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'book-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="book-form-el" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="book-detail" :title="$detail['title'] ?? ''" size="lg">
        @if ($detail)
            <p class="mb-4 text-sm text-ink-muted">{{ $detail['authors'] }}@if ($detail['publisher']) · {{ $detail['publisher'] }}@endif @if ($detail['published_year']) · {{ $detail['published_year'] }}@endif @if ($detail['isbn']) · ISBN {{ $detail['isbn'] }}@endif</p>
            <div class="rounded-md border border-line">
                @foreach ($detail['copy_list'] as $c)
                    <div wire:key="cp-{{ $c['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-4 py-2 text-sm last:border-0">
                        <span class="w-28 font-mono">{{ $c['accession_no'] }}</span>
                        <span class="flex-1 text-xs text-ink-muted">{{ $c['shelf'] ?? '' }}@if ($c['due_on']) · {{ __('due :d', ['d' => \App\Support\Format::date($c['due_on'])]) }}@endif</span>
                        <x-ui.status-badge :status="$c['status']" size="sm" />
                        @canapi('library.catalogue.manage')
                            @if ($c['status'] === 'available')
                                <x-ui.button size="sm" variant="ghost" wire:click="setCopyStatus('{{ $c['id'] }}', 'lost')">{{ __('Lost') }}</x-ui.button>
                                <x-ui.button size="sm" variant="ghost" wire:click="setCopyStatus('{{ $c['id'] }}', 'withdrawn')">{{ __('Withdraw') }}</x-ui.button>
                            @elseif (in_array($c['status'], ['lost', 'withdrawn'], true))
                                <x-ui.button size="sm" variant="ghost" wire:click="setCopyStatus('{{ $c['id'] }}', 'available')">{{ __('Restore') }}</x-ui.button>
                            @endif
                        @endcanapi
                    </div>
                @endforeach
            </div>
            @canapi('library.catalogue.manage')
                <form wire:submit="addCopy" class="mt-4 flex flex-wrap items-end gap-2">
                    <div class="w-40"><x-ui.input :label="__('Accession no.')" wire:model="copy.accession_no" required /></div>
                    <div class="w-32"><x-ui.input :label="__('Shelf')" wire:model="copy.shelf" optional /></div>
                    <x-ui.button type="submit" size="sm" icon="plus" loading="addCopy">{{ __('Add copy') }}</x-ui.button>
                </form>
            @endcanapi
        @endif
    </x-ui.modal>
</div>
