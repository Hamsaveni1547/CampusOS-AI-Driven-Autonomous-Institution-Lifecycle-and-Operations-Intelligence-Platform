@php($s = $cycle['status'])
@php($editableTimetable = $cycle['can']['manage'] && ! in_array($s, ['results_processed', 'results_verified', 'published'], true))
<div>
    <x-ui.page-header :title="$cycle['name']" :description="$cycle['term']['name'].' · '.\App\Support\Format::label($cycle['type'])" :back="route('exam-cycles.index')" :back-label="__('Examinations')">
        <x-slot:meta><x-ui.status-badge :status="$s" :label="$statuses[$s] ?? null" /></x-slot:meta>
        <x-slot:actions>
            @if ($cycle['can']['manage'] && $s === 'draft')
                <x-ui.button icon="door-open" wire:click="step('open-registration')" wire:confirm="{{ __('Open registration? A regular cycle registers every student for their courses.') }}">{{ __('Open registration') }}</x-ui.button>
            @elseif ($cycle['can']['manage'] && $s === 'registration_open')
                <x-ui.button icon="lock" wire:click="step('close-registration')" wire:confirm="{{ __('Close registration and decide eligibility?') }}">{{ __('Close registration') }}</x-ui.button>
            @endif
            @if ($cycle['can']['manage'] && in_array($s, ['registration_closed', 'results_processed'], true))
                <x-ui.button variant="secondary" icon="refresh-cw" wire:click="step('eligibility')">{{ __('Recheck eligibility') }}</x-ui.button>
            @endif
            @if ($cycle['can']['process'])
                <x-ui.button icon="calculator" wire:click="step('process')" loading="step">{{ $s === 'results_processed' ? __('Process again') : __('Process results') }}</x-ui.button>
            @endif
            @if ($cycle['can']['verify'])
                <x-ui.button icon="check" wire:click="step('verify')">{{ __('Verify results') }}</x-ui.button>
            @endif
            @if ($cycle['can']['publish'])
                <x-ui.button icon="send" wire:click="step('publish')" wire:confirm="{{ __('Publish results to students? Published results change only through revaluation.') }}">{{ __('Publish') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat :label="__('Registrations')" :value="$cycle['registrations'] ?? 0" icon="users" />
        <x-ui.stat :label="__('Eligible')" :value="$cycle['eligibility']['eligible']" icon="circle-check" tone="success" />
        <x-ui.stat :label="__('Not eligible')" :value="$cycle['eligibility']['not_eligible']" icon="circle-x" tone="danger" />
    </div>

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['timetable' => __('Timetable'), 'registrations' => __('Registrations'), 'results' => __('Results')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('tab', '{{ $value }}')" :active="$tab === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>

    @if ($tab === 'timetable')
        @if ($editableTimetable)
            <div class="mb-4 flex justify-end"><x-ui.button icon="plus" wire:click="openSchedule">{{ __('Add exam') }}</x-ui.button></div>
        @endif
        <x-ui.table :empty="$cycle['schedules'] === []">
            <x-slot:head>
                <x-ui.th>{{ __('Date') }}</x-ui.th>
                <x-ui.th>{{ __('Course') }}</x-ui.th>
                <x-ui.th>{{ __('Max marks') }}</x-ui.th>
                <x-ui.th>{{ __('Candidates') }}</x-ui.th>
                <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
            </x-slot:head>
            @foreach ($cycle['schedules'] as $exam)
                <x-ui.tr wire:key="ex-{{ $exam['id'] }}">
                    <x-ui.td primary>{{ \App\Support\Format::date($exam['exam_date'], 'D, d M Y') }}<span class="block font-mono text-xs text-ink-muted">{{ $exam['starts_at'] }}–{{ $exam['ends_at'] }}</span></x-ui.td>
                    <x-ui.td :label="__('Course')"><span class="font-medium">{{ $exam['course']['code'] }}</span> <span class="text-xs text-ink-muted">{{ $exam['course']['title'] }}</span></x-ui.td>
                    <x-ui.td :label="__('Max marks')">{{ $exam['max_marks'] + 0 }}</x-ui.td>
                    <x-ui.td :label="__('Candidates')">{{ $exam['candidates'] }}</x-ui.td>
                    <x-ui.td class="text-right">
                        @if ($cycle['can']['enter_marks'])
                            <x-ui.button size="sm" variant="secondary" icon="pencil-line" :href="route('exam-marks.edit', $exam['id'])">{{ __('Marks') }}</x-ui.button>
                        @endif
                        @if ($editableTimetable)
                            <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="openSchedule('{{ $exam['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>
                            <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="deleteSchedule('{{ $exam['id'] }}')" wire:confirm="{{ __('Remove this exam?') }}"><span class="sr-only">{{ __('Delete') }}</span></x-ui.button>
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="calendar" :title="__('No exams scheduled')" /></x-slot:emptyState>
        </x-ui.table>
    @elseif ($tab === 'registrations')
        <div class="mb-4 flex flex-wrap gap-3">
            <div class="w-64"><x-ui.input icon="search" :placeholder="__('Name or register number')" wire:model.live.debounce.300ms="search" /></div>
            <div class="w-48"><x-ui.select wire:model.live="filters.eligibility" :options="['eligible' => __('Eligible'), 'not_eligible' => __('Not eligible'), 'pending' => __('Pending')]" :placeholder="__('Any eligibility')" /></div>
        </div>
        <x-ui.table loading="filters,search,gotoPage" :empty="$rows->isEmpty()">
            <x-slot:head>
                <x-ui.th>{{ __('Student') }}</x-ui.th>
                <x-ui.th>{{ __('Course') }}</x-ui.th>
                <x-ui.th>{{ __('Eligibility') }}</x-ui.th>
                <x-ui.th>{{ __('Internal') }}</x-ui.th>
                <x-ui.th>{{ __('Exam') }}</x-ui.th>
                <x-ui.th>{{ __('Grade') }}</x-ui.th>
            </x-slot:head>
            @foreach ($rows->items as $row)
                <x-ui.tr wire:key="rg-{{ $row['id'] }}">
                    <x-ui.td primary>
                        <span class="font-medium text-ink">{{ $row['student']['name'] }}</span>
                        <span class="block font-mono text-xs text-ink-subtle">{{ $row['student']['register_no'] }}@if ($row['script_code']) · {{ __('script :c', ['c' => $row['script_code']]) }}@endif</span>
                    </x-ui.td>
                    <x-ui.td :label="__('Course')">{{ $row['course']['code'] }}@if ($row['kind'] === 'backlog') <x-ui.badge size="sm" tone="warning">{{ __('Backlog') }}</x-ui.badge>@endif</x-ui.td>
                    <x-ui.td :label="__('Eligibility')">
                        <x-ui.status-badge :status="$row['eligibility']" size="sm" />
                        @foreach ($row['reasons'] as $reason)<span class="block text-xs text-danger">{{ $reason }}</span>@endforeach
                        @if ($row['override_reason'])<span class="block text-xs text-ink-muted">{{ __('Override: :r', ['r' => $row['override_reason']]) }}</span>@endif
                        @if ($row['eligibility'] === 'not_eligible' && in_array($s, ['registration_closed', 'results_processed'], true))
                            @canapi('exams.eligibility.override')
                                <x-ui.button size="sm" variant="ghost" wire:click="askOverride('{{ $row['id'] }}')">{{ __('Override') }}</x-ui.button>
                            @endcanapi
                        @endif
                    </x-ui.td>
                    <x-ui.td :label="__('Internal')">{{ $row['internal'] !== null ? ($row['internal'] + 0).' / '.($row['internal_out_of'] + 0) : '—' }}</x-ui.td>
                    <x-ui.td :label="__('Exam')">{{ $row['attendance'] === 'absent' ? __('Absent') : ($row['external'] !== null ? $row['external'] + 0 : '—') }}</x-ui.td>
                    <x-ui.td :label="__('Grade')"><span @class(['font-semibold', 'text-danger' => $row['passed'] === false, 'text-ink' => $row['passed'] !== false])>{{ $row['grade'] ?? '—' }}</span>@if ($row['revision'] > 0) <span class="text-xs text-ink-muted">{{ __('rev. :n', ['n' => $row['revision']]) }}</span>@endif</x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No registrations')" /></x-slot:emptyState>
            <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
        </x-ui.table>
    @else
        @if ($results === null)
            <x-ui.empty-state icon="calculator" :title="__('Results not processed yet')" :description="__('Enter marks for every exam, then process results.')" />
        @else
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <x-ui.stat :label="__('Pass rate')" :value="($results['summary']['pass_percent'] ?? 0).'%'" icon="percent" />
                <x-ui.stat :label="__('All courses cleared')" :value="$results['summary']['all_clear'].' / '.$results['summary']['students']" icon="graduation-cap" tone="success" />
                <x-ui.card :title="__('Grades')"><div class="flex flex-wrap gap-2">@foreach ($results['summary']['grades'] as $grade => $count)<x-ui.badge wire:key="gd-{{ $grade }}">{{ $grade }}: {{ $count }}</x-ui.badge>@endforeach</div></x-ui.card>
            </div>
            <x-ui.table :empty="$results['students'] === []">
                <x-slot:head>
                    <x-ui.th>{{ __('Student') }}</x-ui.th>
                    <x-ui.th>{{ __('Grades') }}</x-ui.th>
                    <x-ui.th>{{ __('Credits') }}</x-ui.th>
                    <x-ui.th>{{ __('SGPA') }}</x-ui.th>
                    <x-ui.th>{{ __('CGPA') }}</x-ui.th>
                    <x-ui.th>{{ __('Backlogs') }}</x-ui.th>
                </x-slot:head>
                @foreach ($results['students'] as $student)
                    <x-ui.tr wire:key="rs-{{ $student['id'] }}">
                        <x-ui.td primary><span class="font-medium text-ink">{{ $student['name'] }}</span><span class="block font-mono text-xs text-ink-subtle">{{ $student['register_no'] }}</span></x-ui.td>
                        <x-ui.td :label="__('Grades')">@foreach ($student['grades'] as $g)<span class="mr-2 text-xs"><span class="text-ink-muted">{{ $g['course'] }}</span> <span class="font-medium">{{ $g['grade'] }}</span></span>@endforeach</x-ui.td>
                        <x-ui.td :label="__('Credits')">{{ $student['credits_earned'] + 0 }} / {{ $student['credits_registered'] + 0 }}</x-ui.td>
                        <x-ui.td :label="__('SGPA')">{{ $student['sgpa'] ?? '—' }}</x-ui.td>
                        <x-ui.td :label="__('CGPA')">{{ $student['cgpa'] ?? '—' }}</x-ui.td>
                        <x-ui.td :label="__('Backlogs')"><span @class(['text-danger font-medium' => $student['backlogs'] > 0])>{{ $student['backlogs'] }}</span></x-ui.td>
                    </x-ui.tr>
                @endforeach
                <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No results')" /></x-slot:emptyState>
            </x-ui.table>
        @endif
    @endif

    <x-ui.modal name="schedule-form" :title="($schedule['id'] ?? null) ? __('Edit exam') : __('Add exam')">
        <form wire:submit="saveSchedule" id="schedule-form-el" class="grid gap-4 sm:grid-cols-2">
            <x-ui.select :label="__('Course')" wire:model="schedule.course_id" :options="$courses" :placeholder="__('Choose…')" required class="sm:col-span-2" />
            <x-ui.input type="date" :label="__('Date')" wire:model="schedule.exam_date" required />
            <x-ui.input type="number" step="0.01" min="1" :label="__('Maximum marks')" wire:model="schedule.max_marks" required />
            <x-ui.input type="time" :label="__('Starts')" wire:model="schedule.starts_at" required />
            <x-ui.input type="time" :label="__('Ends')" wire:model="schedule.ends_at" required />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'schedule-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="schedule-form-el" loading="saveSchedule">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="override" :title="__('Make eligible')" size="sm">
        <form wire:submit="override" id="override-form"><x-ui.textarea :label="__('Reason')" wire:model="reason" rows="2" required :hint="__('e.g. medical leave approved by the principal')" /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'override')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="override-form" loading="override">{{ __('Make eligible') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
