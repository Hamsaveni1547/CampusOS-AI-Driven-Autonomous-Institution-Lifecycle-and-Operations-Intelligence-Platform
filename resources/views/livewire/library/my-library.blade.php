<div>
    <x-ui.page-header :title="__('My library')" :description="__('Late returns are fined ₹:f per day.', ['f' => $rules['fine_per_day']])">
        <x-slot:actions><x-ui.button variant="secondary" icon="search" :href="route('library.catalogue')">{{ __('Search catalogue') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    @if ($data['dues']['fines'] > 0)
        <x-ui.alert class="mb-4" tone="danger">{{ __('Pending fine: ₹:f. Pay at the library counter before borrowing again.', ['f' => number_format($data['dues']['fines'], 2)]) }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card :title="__('On loan')" :padding="false">
            @forelse ($data['current'] as $l)
                <div wire:key="ml-{{ $l['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-ink">{{ $l['book']['title'] }}</p>
                        <p class="text-xs text-ink-muted">{{ $l['book']['authors'] }} · <span @class(['text-danger font-medium' => $l['overdue']])>{{ $l['overdue'] ? __('overdue since :d', ['d' => \App\Support\Format::date($l['due_on'])]) : __('due :d', ['d' => \App\Support\Format::date($l['due_on'])]) }}</span></p>
                    </div>
                    @if (! $l['overdue'] && $l['renewals'] < $rules['max_renewals'])
                        <x-ui.button size="sm" variant="ghost" icon="refresh-cw" wire:click="renew('{{ $l['id'] }}')">{{ __('Renew') }}</x-ui.button>
                    @endif
                </div>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No books on loan.') }}</p>
            @endforelse
        </x-ui.card>

        <x-ui.card :title="__('Returned')" :padding="false">
            @forelse ($data['history'] as $l)
                <div wire:key="mh-{{ $l['id'] }}" class="flex items-center gap-3 border-b border-line px-5 py-2.5 text-sm last:border-0">
                    <span class="min-w-0 flex-1 text-ink">{{ $l['book']['title'] }}</span>
                    <span class="text-xs text-ink-muted">{{ \App\Support\Format::date($l['returned_on']) }}</span>
                    @if ($l['fine'] > 0)<x-ui.badge size="sm" :tone="$l['fine_status'] === 'due' ? 'danger' : 'neutral'">₹{{ number_format($l['fine'], 0) }} {{ $l['fine_status'] === 'due' ? __('due') : __($l['fine_status']) }}</x-ui.badge>@endif
                </div>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('Nothing yet.') }}</p>
            @endforelse
        </x-ui.card>
    </div>
</div>
