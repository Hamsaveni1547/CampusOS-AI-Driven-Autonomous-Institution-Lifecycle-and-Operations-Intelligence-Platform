<div>
    <x-ui.page-header :title="__('Faculty workload')" :description="($report['term']['name'] ?? __('No current term')).($report['limit'] ? ' · '.__('limit :n hours a week', ['n' => $report['limit']]) : '')" :back="route('course-offerings.index')" :back-label="__('Course offerings')" />

    <div class="mb-4 flex flex-wrap gap-3">
        <x-ui.select wire:model.live="term" :options="$terms" :placeholder="__('Current term')" class="sm:w-64" :aria-label="__('Term')" />
        <x-ui.select wire:model.live="department" :options="$departments" :placeholder="__('All departments')" class="sm:w-56" :aria-label="__('Department')" />
    </div>

    <x-ui.table loading="term,department" :empty="($report['faculty'] ?? []) === []">
        <x-slot:head>
            <x-ui.th>{{ __('Teacher') }}</x-ui.th>
            <x-ui.th>{{ __('Hours / week') }}</x-ui.th>
            <x-ui.th>{{ __('Classes') }}</x-ui.th>
        </x-slot:head>
        @foreach ($report['faculty'] ?? [] as $row)
            <x-ui.tr wire:key="wl-{{ $row['employee_id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('staff.show', $row['employee_id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $row['name'] }}</a>
                    <p class="text-xs text-ink-muted">{{ $row['department'] ?? '—' }}</p>
                </x-ui.td>
                <x-ui.td :label="__('Hours / week')">
                    <span @class(['font-semibold', 'text-danger' => $row['over_limit']])>{{ $row['weekly_hours'] }}</span>
                    @if ($row['over_limit'])<x-ui.badge size="sm" tone="danger">{{ __('Over limit') }}</x-ui.badge>@endif
                </x-ui.td>
                <x-ui.td :label="__('Classes')">
                    <div class="flex flex-wrap gap-1">
                        @foreach ($row['classes'] as $class)
                            <x-ui.badge size="sm">{{ $class['course'] }} {{ $class['section'] }} · {{ $components[$class['component']] ?? $class['component'] }} {{ $class['weekly_hours'] }}h</x-ui.badge>
                        @endforeach
                    </div>
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="gauge" :title="__('No teachers allocated in this term')" /></x-slot:emptyState>
    </x-ui.table>
</div>
