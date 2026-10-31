<div>
    <x-ui.page-header :title="__('Attendance shortage')" :description="__('Students below the minimum attendance, course by course.')">
        <x-slot:actions>
            @canapi('attendance.corrections.approve')
                <x-ui.button variant="secondary" icon="file-pen" :href="route('attendance-corrections.index')">{{ __('Correction requests') }}</x-ui.button>
            @endcanapi
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap gap-3">
        <x-ui.combobox wire:model.live="section" :options="$sections" class="sm:w-64" :aria-label="__('Section')" :placeholder="__('Choose a section')" />
        <x-ui.select wire:model.live="term" :options="$terms" :placeholder="__('Current term')" class="sm:w-56" :aria-label="__('Term')" />
        <x-ui.select wire:model.live="standing" :options="['shortage,condonable' => __('Shortage and condonable'), 'shortage' => __('Shortage only'), 'condonable' => __('Can be condoned'), 'condoned' => __('Condoned')]" class="sm:w-56" :aria-label="__('Show')" />
    </div>

    @if ($section === '')
        <x-ui.empty-state icon="users" :title="__('Choose a section')" />
    @else
        <x-ui.table loading="section,term,standing" :empty="$rows === []">
            <x-slot:head>
                <x-ui.th>{{ __('Student') }}</x-ui.th>
                <x-ui.th>{{ __('Course') }}</x-ui.th>
                <x-ui.th>{{ __('Attended') }}</x-ui.th>
                <x-ui.th>{{ __('Standing') }}</x-ui.th>
                <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
            </x-slot:head>
            @foreach ($rows as $row)
                <x-ui.tr wire:key="sh-{{ $row['student_id'] }}-{{ $row['class_id'] }}">
                    <x-ui.td primary>
                        <a href="{{ route('students.show', $row['student_id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $row['name'] }}</a>
                        <span class="block font-mono text-xs text-ink-subtle">{{ $row['register_no'] }}</span>
                    </x-ui.td>
                    <x-ui.td :label="__('Course')"><a href="{{ route('attendance.class', $row['class_id']) }}" wire:navigate class="hover:underline">{{ $row['course']['code'] }}</a> <span class="text-xs text-ink-muted">{{ $row['course']['title'] }}</span></x-ui.td>
                    <x-ui.td :label="__('Attended')"><span class="font-semibold">{{ $row['percentage'] }}%</span> <span class="text-xs text-ink-muted">({{ $row['attended'] }}/{{ $row['held'] }})</span></x-ui.td>
                    <x-ui.td :label="__('Standing')"><x-attendance.standing :standing="$row['standing']" /></x-ui.td>
                    <x-ui.td class="text-right">
                        @if ($canCondone && $row['standing'] === 'condonable')
                            <x-ui.button size="sm" variant="secondary" wire:click="openCondone('{{ $row['student_id'] }}', '{{ $row['class_id'] }}', @js($row['name'].' · '.$row['course']['code']))">{{ __('Condone') }}</x-ui.button>
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="circle-check" :title="__('Nobody here')" /></x-slot:emptyState>
        </x-ui.table>
    @endif

    @if ($canCondone)
        <x-ui.modal name="condone-form" :title="__('Condone shortage')" :description="$condone['name']" size="sm">
            <form wire:submit="saveCondone" id="condone-form" class="space-y-4">
                <x-ui.textarea :label="__('Reason')" wire:model="condone.reason" rows="2" required />
                <x-ui.input :label="__('Fee receipt')" wire:model="condone.fee_reference" optional />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'condone-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="condone-form" loading="saveCondone">{{ __('Condone') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
