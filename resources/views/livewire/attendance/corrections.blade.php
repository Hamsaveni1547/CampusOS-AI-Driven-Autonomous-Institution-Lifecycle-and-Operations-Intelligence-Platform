<div>
    <x-ui.page-header :title="__('Attendance corrections')" :description="__('Requests to change attendance after the marking window closed.')" />

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['pending' => __('Waiting'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('filters.status', '{{ $value }}')" :active="$filters['status'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>

    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Student') }}</x-ui.th>
            <x-ui.th>{{ __('Class') }}</x-ui.th>
            <x-ui.th>{{ __('Change') }}</x-ui.th>
            <x-ui.th>{{ __('Reason') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $row)
            <x-ui.tr wire:key="cr-{{ $row['id'] }}">
                <x-ui.td primary>
                    <span class="font-medium text-ink">{{ $row['student']['name'] }}</span>
                    <span class="block font-mono text-xs text-ink-subtle">{{ $row['student']['register_no'] }}</span>
                </x-ui.td>
                <x-ui.td :label="__('Class')">{{ $row['course'] }} · {{ $row['section'] }}<span class="block text-xs text-ink-muted">{{ \App\Support\Format::date($row['date']) }}@if ($row['slot']) · {{ $row['slot'] }}@endif</span></x-ui.td>
                <x-ui.td :label="__('Change')">{{ $labels[$row['current_status']] ?? $row['current_status'] }} → <span class="font-medium">{{ $labels[$row['requested_status']] ?? $row['requested_status'] }}</span></x-ui.td>
                <x-ui.td :label="__('Reason')">
                    <span class="text-sm">{{ $row['reason'] }}</span>
                    <span class="block text-xs text-ink-subtle">{{ __('by :name', ['name' => $row['requested_by']]) }}</span>
                    @if ($row['remarks'])<span class="block text-xs text-danger">{{ $row['remarks'] }}</span>@endif
                </x-ui.td>
                <x-ui.td class="text-right">
                    @if ($row['status'] === 'pending')
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

    <x-ui.modal name="reject-correction" :title="__('Reject request')" size="sm">
        <form wire:submit="reject" id="reject-correction-form">
            <x-ui.input :label="__('Reason')" wire:model="remarks" required />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reject-correction')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reject-correction-form" variant="danger" loading="reject">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
