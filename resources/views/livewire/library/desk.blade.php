@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div>
    <x-ui.page-header :title="__('Circulation')" :description="__('Issue and return books by accession number.')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-ui.stat :label="__('Titles')" :value="$summary['titles']" icon="library" />
        <x-ui.stat :label="__('On loan')" :value="$summary['on_loan'].' / '.$summary['copies']" icon="book-open" />
        <x-ui.stat :label="__('Overdue')" :value="$summary['overdue']" icon="clock-alert" tone="danger" />
        <x-ui.stat :label="__('Fines due')" :value="$money($summary['fines_due'])" icon="wallet" tone="warning" />
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card :title="__('Issue')">
            <form wire:submit="lookup" class="mb-3 flex items-end gap-2">
                <div class="flex-1"><x-ui.input :label="__('Member')" wire:model="member" :placeholder="__('Email, register no. or employee code')" /></div>
                <x-ui.button type="submit" variant="secondary" loading="lookup">{{ __('Find') }}</x-ui.button>
            </form>
            @if ($account)
                <div class="mb-3 rounded-md bg-surface-muted p-3 text-sm">
                    <p class="font-medium text-ink">{{ $account['member']['name'] }} <x-ui.badge size="sm">{{ $account['member']['type'] === 'student' ? __('Student') : __('Staff') }}</x-ui.badge></p>
                    <p class="text-xs text-ink-muted">{{ __(':n on loan · :o overdue', ['n' => $account['dues']['on_loan'], 'o' => $account['dues']['overdue']]) }}@if ($account['dues']['fines'] > 0) · <span class="text-danger">{{ __('fine :f', ['f' => $money($account['dues']['fines'])]) }}</span>@endif</p>
                    @foreach ($account['current'] as $l)
                        <p wire:key="cl-{{ $l['id'] }}" class="mt-1 text-xs"><span class="font-mono">{{ $l['accession_no'] }}</span> {{ $l['book']['title'] }} · <span @class(['text-danger' => $l['overdue']])>{{ __('due :d', ['d' => \App\Support\Format::date($l['due_on'])]) }}</span></p>
                    @endforeach
                    @foreach ($account['history'] as $l)
                        @if ($l['fine_status'] === 'due')
                            <p wire:key="hf-{{ $l['id'] }}" class="mt-1 flex items-center gap-2 text-xs"><span class="flex-1">{{ $l['book']['title'] }} · {{ __('fine :f', ['f' => $money($l['fine'])]) }}</span><x-ui.button size="sm" wire:click="collect('{{ $l['id'] }}')">{{ __('Collect') }}</x-ui.button></p>
                        @endif
                    @endforeach
                </div>
                <form wire:submit="issue" class="flex items-end gap-2">
                    <div class="flex-1"><x-ui.input :label="__('Accession no.')" wire:model="issueAccession" required /></div>
                    <x-ui.button type="submit" icon="book-up" loading="issue">{{ __('Issue') }}</x-ui.button>
                </form>
            @endif
            @if ($formError)<x-ui.alert class="mt-3" tone="danger">{{ $formError }}</x-ui.alert>@endif
        </x-ui.card>

        <x-ui.card :title="__('Return')">
            <form wire:submit="returnBook" class="flex items-end gap-2">
                <div class="flex-1"><x-ui.input :label="__('Accession no.')" wire:model="returnAccession" required /></div>
                <x-ui.button type="submit" icon="book-down" loading="returnBook">{{ __('Return') }}</x-ui.button>
            </form>
            <p class="mt-3 text-xs text-ink-muted">{{ __('Late returns are fined automatically; collect or waive the fine below.') }}</p>
        </x-ui.card>
    </div>

    <div class="mb-4 flex gap-5 border-b border-line">
        @foreach (['overdue' => __('Overdue'), 'active' => __('On loan'), 'fines' => __('Fines due')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('filters.state', '{{ $value }}')" :active="$filters['state'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>
    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Book') }}</x-ui.th>
            <x-ui.th>{{ __('Member') }}</x-ui.th>
            <x-ui.th>{{ __('Due') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $l)
            <x-ui.tr wire:key="ln-{{ $l['id'] }}">
                <x-ui.td primary><span class="font-medium text-ink">{{ $l['book']['title'] }}</span><span class="block font-mono text-xs text-ink-subtle">{{ $l['accession_no'] }}</span></x-ui.td>
                <x-ui.td :label="__('Member')">{{ $l['member']['name'] }}<span class="block text-xs text-ink-muted">{{ $l['member']['type'] === 'student' ? __('Student') : __('Staff') }}</span></x-ui.td>
                <x-ui.td :label="__('Due')"><span @class(['text-danger' => $l['overdue']])>{{ \App\Support\Format::date($l['due_on']) }}</span>@if ($l['fine_status'] === 'due')<span class="block text-xs text-danger">{{ __('fine :f', ['f' => $money($l['fine'])]) }}</span>@endif</x-ui.td>
                <x-ui.td class="text-right">
                    @if ($l['fine_status'] === 'due')
                        <x-ui.button size="sm" wire:click="collect('{{ $l['id'] }}')">{{ __('Collect') }}</x-ui.button>
                        @canapi('library.fines.waive')<x-ui.button size="sm" variant="ghost" wire:click="askWaive('{{ $l['id'] }}')">{{ __('Waive') }}</x-ui.button>@endcanapi
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="circle-check" :title="__('Nothing here')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="waive-fine" :title="__('Waive fine')" size="sm">
        <form wire:submit="waive" id="waive-fine-form"><x-ui.input :label="__('Reason')" wire:model="note" required /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'waive-fine')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="waive-fine-form" loading="waive">{{ __('Waive') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
