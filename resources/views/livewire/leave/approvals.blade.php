<div>
    <x-ui.page-header :title="__('Staff leave')" :description="__('Approve leave, and see who is away.')">
        <x-slot:actions><x-ui.button variant="secondary" icon="calendar-x" wire:click="onLeaveToday">{{ __('On leave today') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['pending' => __('Waiting'), 'approved' => __('Approved'), 'rejected' => __('Rejected'), 'cancelled' => __('Cancelled')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="showStatus('{{ $value }}')" :active="$filters['status'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>
    @if ($filters['from'])<p class="mb-4 text-sm text-ink-muted">{{ __('On leave on :d', ['d' => \App\Support\Format::date($filters['from'])]) }}</p>@endif

    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Staff') }}</x-ui.th>
            <x-ui.th>{{ __('Leave') }}</x-ui.th>
            <x-ui.th>{{ __('Reason') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $a)
            <x-ui.tr wire:key="sl-{{ $a['id'] }}">
                <x-ui.td primary><span class="font-medium text-ink">{{ $a['employee']['name'] }}</span><span class="block text-xs text-ink-subtle">{{ $a['employee']['code'] }} · {{ $a['employee']['department'] ?? '—' }}</span></x-ui.td>
                <x-ui.td :label="__('Leave')">{{ $a['type']['name'] }}<span class="block text-xs text-ink-muted">{{ \App\Support\Format::date($a['from_date']) }}@if ($a['to_date'] !== $a['from_date']) – {{ \App\Support\Format::date($a['to_date']) }}@endif · {{ $a['half_day'] ? __('half day') : __(':d day(s)', ['d' => $a['days'] + 0]) }}</span></x-ui.td>
                <x-ui.td :label="__('Reason')">{{ $a['reason'] }}@if ($a['remarks'])<span class="block text-xs text-danger">{{ $a['remarks'] }}</span>@endif @if ($a['decided_by'])<span class="block text-xs text-ink-subtle">{{ __('by :n', ['n' => $a['decided_by']]) }}</span>@endif</x-ui.td>
                <x-ui.td class="text-right">
                    @if ($a['can_decide'])
                        <x-ui.button size="sm" icon="check" wire:click="approve('{{ $a['id'] }}')">{{ __('Approve') }}</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" wire:click="askReject('{{ $a['id'] }}')">{{ __('Reject') }}</x-ui.button>
                    @else
                        <x-ui.status-badge :status="$a['status']" />
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="inbox" :title="__('Nothing here')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="reject-leave" :title="__('Reject leave')" size="sm">
        <form wire:submit="reject" id="reject-leave-form"><x-ui.input :label="__('Reason')" wire:model="remarks" required /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reject-leave')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reject-leave-form" variant="danger" loading="reject">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
