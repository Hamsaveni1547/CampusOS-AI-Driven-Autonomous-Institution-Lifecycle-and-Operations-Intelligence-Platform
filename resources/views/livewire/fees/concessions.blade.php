@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div>
    <x-ui.page-header :title="__('Fee concessions')" :description="__('Concessions are applied to a demand once approved.')" />

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['pending' => __('Waiting'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('filters.status', '{{ $value }}')" :active="$filters['status'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>

    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Student') }}</x-ui.th>
            <x-ui.th>{{ __('Demand') }}</x-ui.th>
            <x-ui.th>{{ __('Concession') }}</x-ui.th>
            <x-ui.th>{{ __('Reason') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $row)
            <x-ui.tr wire:key="fc-{{ $row['id'] }}">
                <x-ui.td primary><span class="font-medium text-ink">{{ $row['student']['name'] }}</span><span class="block font-mono text-xs text-ink-subtle">{{ $row['student']['register_no'] }}</span></x-ui.td>
                <x-ui.td :label="__('Demand')">{{ $row['demand']['title'] }}<span class="block text-xs text-ink-muted">{{ __('balance :b of :a', ['b' => $money($row['demand']['balance']), 'a' => $money($row['demand']['amount'])]) }}</span></x-ui.td>
                <x-ui.td :label="__('Concession')"><span class="font-semibold">{{ $money($row['amount']) }}</span></x-ui.td>
                <x-ui.td :label="__('Reason')">{{ $row['reason'] }}<span class="block text-xs text-ink-subtle">{{ __('by :name', ['name' => $row['requested_by']]) }}</span>@if ($row['remarks'])<span class="block text-xs text-danger">{{ $row['remarks'] }}</span>@endif</x-ui.td>
                <x-ui.td class="text-right">
                    @if ($row['can_decide'])
                        <x-ui.button size="sm" icon="check" wire:click="approve('{{ $row['id'] }}')">{{ __('Approve') }}</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" wire:click="askReject('{{ $row['id'] }}')">{{ __('Reject') }}</x-ui.button>
                    @else
                        <x-ui.status-badge :status="$row['status']" />
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="inbox" :title="__('Nothing here')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="reject-concession" :title="__('Reject concession')" size="sm">
        <form wire:submit="reject" id="reject-concession-form"><x-ui.input :label="__('Reason')" wire:model="remarks" required /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reject-concession')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reject-concession-form" variant="danger" loading="reject">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
