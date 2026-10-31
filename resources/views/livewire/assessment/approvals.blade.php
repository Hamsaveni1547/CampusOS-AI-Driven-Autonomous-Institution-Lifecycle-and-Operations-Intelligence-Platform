<div>
    <x-ui.page-header :title="__('Internal marks approval')" :description="__('Verify and lock submitted internal marks, and decide mark corrections.')" />

    <div class="mb-6 flex gap-5 border-b border-line">
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'sheets')" :active="$tab === 'sheets'">{{ __('Internal marks') }}</x-ui.tab-link>
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'corrections')" :active="$tab === 'corrections'">{{ __('Corrections') }}</x-ui.tab-link>
    </div>

    @if ($tab === 'sheets')
        <div class="mb-4 max-w-xs"><x-ui.select :label="__('Status')" wire:model.live="sheetStatus" :options="$sheetStatuses" /></div>
        <x-ui.table :empty="$sheets === []">
            <x-slot:head>
                <x-ui.th>{{ __('Class') }}</x-ui.th>
                <x-ui.th>{{ __('Students') }}</x-ui.th>
                <x-ui.th>{{ __('Submitted') }}</x-ui.th>
                <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
            </x-slot:head>
            @foreach ($sheets as $sheet)
                <x-ui.tr wire:key="sh-{{ $sheet['id'] }}">
                    <x-ui.td primary>
                        <a href="{{ route('assessment.class', ['class' => $sheet['class']['id'], 'tab' => 'internal']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $sheet['class']['course']['code'] }} · {{ $sheet['class']['course']['title'] }}</a>
                        <span class="block text-xs text-ink-muted">{{ $sheet['class']['section'] }}</span>
                    </x-ui.td>
                    <x-ui.td :label="__('Students')">{{ $sheet['students'] }}</x-ui.td>
                    <x-ui.td :label="__('Submitted')">{{ $sheet['submitted_by'] ?? '—' }}@if ($sheet['submitted_at'])<span class="block text-xs text-ink-muted">{{ \App\Support\Format::dateTime($sheet['submitted_at']) }}</span>@endif</x-ui.td>
                    <x-ui.td class="text-right">
                        @if ($sheet['can_verify'])
                            <x-ui.button size="sm" icon="check" wire:click="verify('{{ $sheet['id'] }}')">{{ __('Verify') }}</x-ui.button>
                        @elseif ($sheet['can_lock'])
                            <x-ui.button size="sm" icon="lock" wire:click="lock('{{ $sheet['id'] }}')" wire:confirm="{{ __('Lock internal marks for examinations?') }}">{{ __('Lock') }}</x-ui.button>
                        @else
                            <x-ui.status-badge :status="$sheet['status']" />
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="inbox" :title="__('Nothing here')" /></x-slot:emptyState>
        </x-ui.table>
    @else
        <div class="mb-4 flex gap-2">
            @foreach (['pending' => __('Waiting'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $value => $label)
                <x-ui.button size="sm" :variant="$filters['status'] === $value ? 'primary' : 'secondary'" wire:click="$set('filters.status', '{{ $value }}')">{{ $label }}</x-ui.button>
            @endforeach
        </div>
        <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
            <x-slot:head>
                <x-ui.th>{{ __('Student') }}</x-ui.th>
                <x-ui.th>{{ __('Assessment') }}</x-ui.th>
                <x-ui.th>{{ __('Change') }}</x-ui.th>
                <x-ui.th>{{ __('Reason') }}</x-ui.th>
                <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
            </x-slot:head>
            @foreach ($rows->items as $row)
                @php($show = fn (array $m) => $m['status'] === 'present' ? ($m['marks'] + 0) : ($markStatuses[$m['status']] ?? $m['status']))
                <x-ui.tr wire:key="mc-{{ $row['id'] }}">
                    <x-ui.td primary>
                        <span class="font-medium text-ink">{{ $row['student']['name'] }}</span>
                        <span class="block font-mono text-xs text-ink-subtle">{{ $row['student']['register_no'] }}</span>
                    </x-ui.td>
                    <x-ui.td :label="__('Assessment')">{{ $row['assessment'] }}</x-ui.td>
                    <x-ui.td :label="__('Change')">{{ $show($row['current']) }} → <span class="font-medium">{{ $show($row['requested']) }}</span></x-ui.td>
                    <x-ui.td :label="__('Reason')">
                        <span class="text-sm">{{ $row['reason'] }}</span>
                        <span class="block text-xs text-ink-subtle">{{ __('by :name', ['name' => $row['requested_by']]) }}</span>
                        @if ($row['remarks'])<span class="block text-xs text-danger">{{ $row['remarks'] }}</span>@endif
                    </x-ui.td>
                    <x-ui.td class="text-right">
                        @if ($row['status'] === 'pending')
                            @canapi('assessment.corrections.approve')
                                <x-ui.button size="sm" icon="check" wire:click="approve('{{ $row['id'] }}')">{{ __('Approve') }}</x-ui.button>
                                <x-ui.button size="sm" variant="ghost" wire:click="askReject('{{ $row['id'] }}')">{{ __('Reject') }}</x-ui.button>
                            @else
                                <x-ui.status-badge :status="$row['status']" />
                            @endcanapi
                        @else
                            <x-ui.status-badge :status="$row['status']" />
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="inbox" :title="__('Nothing here')" /></x-slot:emptyState>
            <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
        </x-ui.table>
    @endif

    <x-ui.modal name="reject-mark-correction" :title="__('Reject request')" size="sm">
        <form wire:submit="reject" id="reject-mark-correction-form"><x-ui.input :label="__('Reason')" wire:model="remarks" required /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reject-mark-correction')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reject-mark-correction-form" variant="danger" loading="reject">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
