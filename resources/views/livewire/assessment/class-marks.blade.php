<div>
    <x-ui.page-header :title="$sheet['class']['course']['code'].' · '.$sheet['class']['course']['title']" :description="$sheet['class']['section'].' · '.__('Internal assessment')" :back="route('classes.show', $sheet['class']['id'])" :back-label="__('Class')">
        <x-slot:meta><x-ui.status-badge :status="$sheet['status']" /></x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="file-text" :href="route('assessment.assignments', $sheet['class']['id'])">{{ __('Assignments') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($sheet['remarks'] && $sheet['status'] === 'draft')
        <x-ui.alert class="mb-4" tone="warning">{{ __('Sent back: :r', ['r' => $sheet['remarks']]) }}</x-ui.alert>
    @endif

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['marks' => __('Marks'), 'internal' => __('Internal marks'), 'scheme' => __('Scheme'), 'outcomes' => __('Outcomes')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('tab', '{{ $value }}')" :active="$tab === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>

    @if ($tab === 'marks')
        @if ($assessments === [])
            <x-ui.empty-state icon="clipboard-list" :title="__('No assessments yet')" :description="__('Set up the scheme first: parts such as IA tests and their assessments.')">
                <x-ui.button wire:click="$set('tab', 'scheme')">{{ __('Set up scheme') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="mb-4 max-w-xs"><x-ui.select :label="__('Assessment')" wire:model.live="assessmentId" :options="$assessments" /></div>
            @if ($marks)
                @unless ($marks['editable'])
                    <x-ui.alert class="mb-4" tone="info">{{ __('Internal marks are submitted. To change a mark, ask for a correction.') }}</x-ui.alert>
                @endunless
                <form wire:submit="saveMarks">
                    <x-ui.table :empty="$marks['students'] === []">
                        <x-slot:head>
                            <x-ui.th>{{ __('Student') }}</x-ui.th>
                            <x-ui.th>{{ __('Status') }}</x-ui.th>
                            <x-ui.th>{{ __('Marks (out of :m)', ['m' => $marks['assessment']['max_marks']]) }}</x-ui.th>
                            @unless ($marks['editable'])<x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>@endunless
                        </x-slot:head>
                        @foreach ($marks['students'] as $student)
                            <x-ui.tr wire:key="mk-{{ $student['id'] }}">
                                <x-ui.td primary>
                                    <span class="mr-2 font-mono text-xs text-ink-muted">{{ $student['roll_no'] ?? '—' }}</span>
                                    <span class="font-medium text-ink">{{ $student['name'] }}</span>
                                    <span class="block font-mono text-xs text-ink-subtle">{{ $student['register_no'] }}</span>
                                </x-ui.td>
                                @if ($marks['editable'])
                                    <x-ui.td :label="__('Status')"><div class="w-32"><x-ui.select wire:model.live="entries.{{ $student['id'] }}.status" :options="$markStatuses" /></div></x-ui.td>
                                    <x-ui.td :label="__('Marks')"><div class="w-28"><x-ui.input type="number" step="0.01" min="0" :max="$marks['assessment']['max_marks']" wire:model="entries.{{ $student['id'] }}.marks" :disabled="($entries[$student['id']]['status'] ?? 'present') !== 'present'" /></div></x-ui.td>
                                @else
                                    <x-ui.td :label="__('Status')">{{ $student['status'] ? ($markStatuses[$student['status']] ?? $student['status']) : '—' }}</x-ui.td>
                                    <x-ui.td :label="__('Marks')">{{ $student['marks'] ?? '—' }}</x-ui.td>
                                    <x-ui.td class="text-right">
                                        @if ($student['mark_id'])
                                            <x-ui.button size="sm" variant="ghost" wire:click="openCorrection({{ $student['mark_id'] }}, @js($student['name']), {{ $student['marks'] ?? 'null' }}, '{{ $student['status'] }}')">{{ __('Correct') }}</x-ui.button>
                                        @endif
                                    </x-ui.td>
                                @endif
                            </x-ui.tr>
                        @endforeach
                        <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No students registered')" /></x-slot:emptyState>
                    </x-ui.table>
                    @if ($marks['editable'] && $marks['students'] !== [])
                        <div class="mt-4 flex justify-end"><x-ui.button type="submit" icon="save" loading="saveMarks">{{ __('Save marks') }}</x-ui.button></div>
                    @endif
                </form>
            @endif
        @endif
    @elseif ($tab === 'internal')
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @if ($sheet['can_edit'])
                <x-ui.button variant="secondary" icon="calculator" wire:click="compute" loading="compute">{{ __('Calculate') }}</x-ui.button>
                <x-ui.button icon="send" wire:click="submit" wire:confirm="{{ __('Submit internal marks for verification? Marks cannot be changed afterwards without a correction.') }}" loading="submit">{{ __('Submit') }}</x-ui.button>
            @endif
            @if ($sheet['can_verify'])
                <x-ui.button icon="check" wire:click="verify" loading="verify">{{ __('Verify') }}</x-ui.button>
                <x-ui.button variant="ghost" wire:click="askSendBack">{{ __('Send back') }}</x-ui.button>
            @endif
            @if ($sheet['can_lock'])
                <x-ui.button icon="lock" wire:click="lock" wire:confirm="{{ __('Lock internal marks for examinations?') }}" loading="lock">{{ __('Lock') }}</x-ui.button>
            @endif
            <span class="text-xs text-ink-muted">
                @if ($sheet['computed_at']) {{ __('Calculated :t', ['t' => \App\Support\Format::dateTime($sheet['computed_at'])]) }}@endif
                @if ($sheet['submitted_by']) · {{ __('submitted by :n', ['n' => $sheet['submitted_by']]) }}@endif
                @if ($sheet['verified_by']) · {{ __('verified by :n', ['n' => $sheet['verified_by']]) }}@endif
            </span>
        </div>
        <x-ui.table :empty="$sheet['computed_at'] === null">
            <x-slot:head>
                <x-ui.th>{{ __('Student') }}</x-ui.th>
                @foreach ($sheet['rules']['groups'] ?? [] as $group)
                    <x-ui.th>{{ $group['name'] }} <span class="text-ink-subtle">/{{ $group['weight'] + 0 }}</span></x-ui.th>
                @endforeach
                <x-ui.th>{{ __('Internal') }} <span class="text-ink-subtle">/{{ ($sheet['rules']['total'] ?? 0) + 0 }}</span></x-ui.th>
            </x-slot:head>
            @foreach ($sheet['students'] as $student)
                <x-ui.tr wire:key="im-{{ $student['id'] }}">
                    <x-ui.td primary>
                        <span class="mr-2 font-mono text-xs text-ink-muted">{{ $student['roll_no'] ?? '—' }}</span>
                        <span class="font-medium text-ink">{{ $student['name'] }}</span>
                    </x-ui.td>
                    @foreach ($sheet['rules']['groups'] ?? [] as $i => $group)
                        <x-ui.td :label="$group['name']">{{ $student['breakdown'][$i]['score'] ?? '—' }}</x-ui.td>
                    @endforeach
                    <x-ui.td :label="__('Internal')"><span class="font-semibold text-ink">{{ $student['final'] ?? '—' }}</span></x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="calculator" :title="__('Not calculated yet')" :description="__('Enter marks, then calculate to preview internal marks.')" /></x-slot:emptyState>
        </x-ui.table>
    @elseif ($tab === 'scheme')
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-ink-muted">{{ __('Internal marks out of :t. Each part is scaled to its weight; best-of keeps the top scores.', ['t' => $scheme['total_weight'] + 0]) }}</p>
            @if ($scheme['can_manage'] && $sheet['status'] === 'draft')
                <x-ui.button icon="plus" wire:click="openGroup">{{ __('Add part') }}</x-ui.button>
            @endif
        </div>
        <div class="space-y-4">
            @forelse ($scheme['groups'] as $group)
                <x-ui.card wire:key="gr-{{ $group['id'] }}" :title="$group['name'].' · '.__('weight :w', ['w' => $group['weight'] + 0]).($group['best_of'] ? ' · '.__('best :n', ['n' => $group['best_of']]) : '')" :padding="false">
                    @if ($scheme['can_manage'] && $sheet['status'] === 'draft')
                        <x-slot:actions>
                            <x-ui.button size="sm" variant="ghost" icon="plus" wire:click="openAssessment('{{ $group['id'] }}')">{{ __('Assessment') }}</x-ui.button>
                            <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="openGroup('{{ $group['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>
                            <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="deleteGroup('{{ $group['id'] }}')" wire:confirm="{{ __('Delete this part?') }}"><span class="sr-only">{{ __('Delete') }}</span></x-ui.button>
                        </x-slot:actions>
                    @endif
                    @forelse ($group['assessments'] as $item)
                        <div wire:key="as-{{ $item['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-2.5 text-sm last:border-0">
                            <span class="min-w-0 flex-1 font-medium text-ink">{{ $item['name'] }} <span class="text-xs font-normal text-ink-muted">{{ $kinds[$item['kind']] ?? $item['kind'] }}@if ($item['held_on']) · {{ \App\Support\Format::date($item['held_on']) }}@endif</span></span>
                            @foreach ($item['outcome_codes'] as $code)<x-ui.badge size="sm">{{ $code }}</x-ui.badge>@endforeach
                            <span class="text-ink-muted">{{ __('out of :m', ['m' => $item['max_marks'] + 0]) }}</span>
                            @if ($scheme['can_manage'] && $sheet['status'] === 'draft')
                                <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="openAssessment('{{ $group['id'] }}', '{{ $item['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>
                                <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="deleteAssessment('{{ $item['id'] }}')" wire:confirm="{{ __('Delete this assessment?') }}"><span class="sr-only">{{ __('Delete') }}</span></x-ui.button>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No assessments in this part.') }}</p>
                    @endforelse
                </x-ui.card>
            @empty
                <x-ui.empty-state icon="clipboard-list" :title="__('No scheme yet')" :description="__('Add parts such as IA tests (weight 30, best 2) and Assignments (weight 10).')" />
            @endforelse
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.card :title="__('Course outcomes')" :padding="false">
                @forelse ($outcomes as $item)
                    <div wire:key="co-{{ $item['id'] }}" class="flex items-start gap-3 border-b border-line px-5 py-2.5 text-sm last:border-0">
                        <x-ui.badge size="sm">{{ $item['code'] }}</x-ui.badge>
                        <span class="min-w-0 flex-1 text-ink">{{ $item['description'] }}@if ($item['bloom_level']) <span class="text-xs text-ink-muted">· {{ $blooms[$item['bloom_level']] ?? $item['bloom_level'] }}</span>@endif</span>
                        @canapi('assessment.schemes.manage')
                            <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="deleteOutcome('{{ $item['id'] }}')" wire:confirm="{{ __('Delete this outcome?') }}"><span class="sr-only">{{ __('Delete') }}</span></x-ui.button>
                        @endcanapi
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No course outcomes yet.') }}</p>
                @endforelse
                @canapi('assessment.schemes.manage')
                    <form wire:submit="addOutcome" class="grid gap-3 border-t border-line p-5 sm:grid-cols-[6rem_1fr]">
                        <x-ui.input :label="__('Code')" wire:model="outcome.code" placeholder="CO1" required />
                        <x-ui.input :label="__('Description')" wire:model="outcome.description" required />
                        <x-ui.select :label="__('Bloom level')" wire:model="outcome.bloom_level" :options="$blooms" :placeholder="__('Any')" optional class="sm:col-span-2" />
                        <div class="sm:col-span-2"><x-ui.button type="submit" icon="plus" loading="addOutcome">{{ __('Add outcome') }}</x-ui.button></div>
                    </form>
                @endcanapi
            </x-ui.card>
            <x-ui.card :title="__('Attainment')" :description="__('Students scoring at least :t% on the assessments tagged with each outcome.', ['t' => $target])">
                <div class="space-y-4">
                    @forelse ($attainment as $row)
                        <div wire:key="at-{{ $row['code'] }}">
                            <p class="mb-1 text-sm text-ink"><span class="font-medium">{{ $row['code'] }}</span> <span class="text-xs text-ink-muted">{{ __(':a of :s students · :n assessments', ['a' => $row['attained'], 's' => $row['students'], 'n' => $row['assessments']]) }}</span></p>
                            <x-ui.progress :value="(int) round($row['percentage'] ?? 0)" show-value />
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('Add course outcomes and tag assessments with them.') }}</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    @endif

    <x-ui.modal name="group-form" :title="($group['id'] ?? null) ? __('Edit part') : __('Add part')" size="sm">
        <form wire:submit="saveGroup" id="group-form-el" class="space-y-4">
            <x-ui.input :label="__('Name')" wire:model="group.name" required :hint="__('e.g. IA tests')" />
            <x-ui.input type="number" step="0.01" min="0" :label="__('Weight (marks)')" wire:model="group.weight" required />
            <x-ui.input type="number" min="1" :label="__('Count best')" wire:model="group.best_of" optional :hint="__('Leave empty to average all.')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'group-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="group-form-el" loading="saveGroup">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="assessment-form" :title="($assessment['id'] ?? null) ? __('Edit assessment') : __('Add assessment')">
        <form wire:submit="saveAssessment" id="assessment-form-el" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('Name')" wire:model="assessment.name" required :hint="__('e.g. IA1')" />
            <x-ui.select :label="__('Kind')" wire:model="assessment.kind" :options="$kinds" required />
            <x-ui.input type="number" step="0.01" min="1" :label="__('Maximum marks')" wire:model="assessment.max_marks" required />
            <x-ui.input type="date" :label="__('Held on')" wire:model="assessment.held_on" optional />
            @if ($outcomeCodes !== [])
                <div class="space-y-2 sm:col-span-2">
                    <p class="text-sm font-medium text-ink">{{ __('Course outcomes') }}</p>
                    @foreach ($outcomeCodes as $code => $label)
                        <x-ui.checkbox wire:key="oc-{{ $code }}" wire:model="assessment.outcome_codes" value="{{ $code }}" :label="$label" />
                    @endforeach
                </div>
            @endif
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'assessment-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="assessment-form-el" loading="saveAssessment">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="send-back" :title="__('Send back to the teacher')" size="sm">
        <form wire:submit="sendBack" id="send-back-form"><x-ui.textarea :label="__('What needs to change')" wire:model="remarks" rows="2" required /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'send-back')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="send-back-form" variant="danger" loading="sendBack">{{ __('Send back') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="mark-correction" :title="__('Correct a mark')" size="sm">
        <form wire:submit="requestCorrection" id="mark-correction-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ $correction['student'] ?? '' }}</p>
            <x-ui.select :label="__('Status')" wire:model.live="correction.status" :options="$markStatuses" required />
            @if (($correction['status'] ?? 'present') === 'present')
                <x-ui.input type="number" step="0.01" min="0" :label="__('Correct marks')" wire:model="correction.marks" required />
            @endif
            <x-ui.textarea :label="__('Reason')" wire:model="correction.reason" rows="2" required />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'mark-correction')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="mark-correction-form" loading="requestCorrection">{{ __('Send') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
