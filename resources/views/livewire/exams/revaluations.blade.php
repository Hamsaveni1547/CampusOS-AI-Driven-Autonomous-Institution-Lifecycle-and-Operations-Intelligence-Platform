<div>
    <x-ui.page-header :title="__('Revaluation')" :description="__('Students asking for their exam scripts to be evaluated again.')" />

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['pending' => __('Waiting'), 'completed' => __('Completed'), 'rejected' => __('Rejected')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('filters.status', '{{ $value }}')" :active="$filters['status'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>

    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Student') }}</x-ui.th>
            <x-ui.th>{{ __('Exam') }}</x-ui.th>
            <x-ui.th>{{ __('Marks') }}</x-ui.th>
            <x-ui.th>{{ __('Reason') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $row)
            <x-ui.tr wire:key="rv-{{ $row['id'] }}">
                <x-ui.td primary>
                    <span class="font-medium text-ink">{{ $row['student']['name'] }}</span>
                    <span class="block font-mono text-xs text-ink-subtle">{{ $row['student']['register_no'] }} · {{ __('script :c', ['c' => $row['script_code']]) }}</span>
                </x-ui.td>
                <x-ui.td :label="__('Exam')">{{ $row['course']['code'] }}<span class="block text-xs text-ink-muted">{{ $row['cycle'] }}</span></x-ui.td>
                <x-ui.td :label="__('Marks')">
                    @if ($row['status'] === 'completed') {{ $row['old_marks'] + 0 }} → <span class="font-medium">{{ $row['new_marks'] + 0 }}</span>
                    @else {{ $row['marks'] ?? '—' }} <span class="text-xs text-ink-muted">({{ $row['grade'] }})</span>
                    @endif
                </x-ui.td>
                <x-ui.td :label="__('Reason')">{{ $row['reason'] }}@if ($row['remarks'])<span class="block text-xs text-ink-muted">{{ $row['remarks'] }}</span>@endif</x-ui.td>
                <x-ui.td class="text-right">
                    @if ($row['status'] === 'pending')
                        <x-ui.button size="sm" wire:click="open('{{ $row['id'] }}', @js($row['student']['name']), {{ $row['marks'] ?? 'null' }})">{{ __('Decide') }}</x-ui.button>
                    @else
                        <x-ui.status-badge :status="$row['status']" />
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="inbox" :title="__('Nothing here')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="revaluation" :title="__('Revaluation')" size="sm">
        <form wire:submit="decide" id="revaluation-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ $decision['student'] }} · {{ __('current marks :m', ['m' => $decision['marks'] ?? '—']) }}</p>
            <x-ui.input type="number" step="0.01" min="0" :label="__('Revised marks')" wire:model="decision.new_marks" :hint="__('The grade, SGPA and CGPA are recalculated.')" />
            <x-ui.textarea :label="__('Remarks')" wire:model="decision.remarks" rows="2" optional :hint="__('Required when rejecting.')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="ghost" wire:click="decide(true)" loading="decide">{{ __('Reject') }}</x-ui.button>
            <x-ui.button type="submit" form="revaluation-form" loading="decide">{{ __('Save revised marks') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
