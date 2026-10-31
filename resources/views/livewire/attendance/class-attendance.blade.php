<div>
    <x-ui.page-header :title="$data['class']['course']['code'].' · '.$data['class']['course']['title']" :description="$data['class']['section'].' · '.__('minimum :n%', ['n' => $threshold])" :back="route('classes.show', $data['class']['id'])" :back-label="__('Class')" />

    <x-ui.table :empty="$data['students'] === []">
        <x-slot:head>
            <x-ui.th>{{ __('Student') }}</x-ui.th>
            <x-ui.th>{{ __('Attended') }}</x-ui.th>
            <x-ui.th>{{ __('Percentage') }}</x-ui.th>
            <x-ui.th>{{ __('Standing') }}</x-ui.th>
        </x-slot:head>
        @foreach ($data['students'] as $row)
            <x-ui.tr wire:key="ca-{{ $row['student_id'] }}">
                <x-ui.td primary>
                    <span class="mr-2 font-mono text-xs text-ink-muted">{{ $row['roll_no'] ?? '—' }}</span>
                    <span class="font-medium text-ink">{{ $row['name'] }}</span>
                    <span class="block font-mono text-xs text-ink-subtle">{{ $row['register_no'] }}</span>
                </x-ui.td>
                <x-ui.td :label="__('Attended')">{{ $row['attended'] }} / {{ $row['held'] }}</x-ui.td>
                <x-ui.td :label="__('Percentage')"><div class="w-36"><x-ui.progress :value="(int) round($row['percentage'] ?? 0)" show-value :tone="($row['percentage'] ?? 100) < $threshold ? 'danger' : 'success'" /></div></x-ui.td>
                <x-ui.td :label="__('Standing')"><x-attendance.standing :standing="$row['standing']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No students registered')" /></x-slot:emptyState>
    </x-ui.table>
</div>
