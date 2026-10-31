<div>
    <x-ui.page-header :title="$class['course']['code'].' · '.__('Assignments')" :description="$class['course']['title'].' · '.$class['section']" :back="route('assessment.class', $class['id'])" :back-label="__('Marks')">
        @if ($canManage)
            <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('New assignment') }}</x-ui.button></x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.table :empty="$assignments === []">
        <x-slot:head>
            <x-ui.th>{{ __('Assignment') }}</x-ui.th>
            <x-ui.th>{{ __('Due') }}</x-ui.th>
            <x-ui.th>{{ __('Submissions') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($assignments as $row)
            <x-ui.tr wire:key="asg-{{ $row['id'] }}">
                <x-ui.td primary>
                    <button type="button" wire:click="$set('assignmentId', '{{ $row['id'] }}')" class="font-medium text-ink hover:underline">{{ $row['title'] }}</button>
                    <span class="block text-xs text-ink-muted">{{ __('out of :m', ['m' => $row['max_marks'] + 0]) }}@if ($row['assessment']) · {{ __('counts as :a', ['a' => $row['assessment']['name']]) }}@endif</span>
                </x-ui.td>
                <x-ui.td :label="__('Due')">{{ \App\Support\Format::dateTime($row['due_at']) }}@if ($row['allow_late']) <span class="block text-xs text-ink-muted">{{ __('late allowed') }}</span>@endif</x-ui.td>
                <x-ui.td :label="__('Submissions')">{{ __(':s submitted · :e evaluated', ['s' => $row['submissions'], 'e' => $row['evaluated']]) }}</x-ui.td>
                <x-ui.td class="text-right">
                    @if ($canManage)
                        <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="open('{{ $row['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="delete('{{ $row['id'] }}')" wire:confirm="{{ __('Delete this assignment?') }}"><span class="sr-only">{{ __('Delete') }}</span></x-ui.button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="file-text" :title="__('No assignments yet')" /></x-slot:emptyState>
    </x-ui.table>

    @if ($detail)
        <x-ui.card class="mt-6" :title="$detail['assignment']['title']" :description="$detail['assignment']['instructions']" :padding="false">
            @forelse ($detail['submissions'] as $sub)
                <div wire:key="sub-{{ $sub['id'] }}" class="flex flex-wrap items-start gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                    <div class="w-48 shrink-0">
                        <p class="font-medium text-ink">{{ $sub['student']['name'] }}</p>
                        <p class="font-mono text-xs text-ink-subtle">{{ $sub['student']['register_no'] }}</p>
                        <p class="text-xs text-ink-muted">{{ \App\Support\Format::dateTime($sub['submitted_at']) }}@if ($sub['is_late']) · <span class="text-warning">{{ __('late') }}</span>@endif</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        @if ($sub['text'])<p class="whitespace-pre-line text-ink">{{ $sub['text'] }}</p>@endif
                        @if ($sub['file'])<a href="{{ route('files.download', $sub['file']['id']) }}" class="text-primary hover:underline">{{ $sub['file']['name'] }}</a>@endif
                        @if ($sub['feedback'])<p class="mt-1 text-xs text-ink-muted">{{ __('Feedback: :f', ['f' => $sub['feedback']]) }}</p>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($sub['marks'] !== null)<span class="font-semibold text-ink">{{ $sub['marks'] + 0 }} / {{ $detail['assignment']['max_marks'] + 0 }}</span>@endif
                        @if ($canManage)
                            <x-ui.button size="sm" :variant="$sub['marks'] === null ? 'primary' : 'ghost'" wire:click="openEvaluation('{{ $sub['id'] }}', @js($sub['student']['name']), {{ $sub['marks'] ?? 'null' }}, @js($sub['feedback']))">{{ $sub['marks'] === null ? __('Evaluate') : __('Change') }}</x-ui.button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-ink-muted">{{ __('No submissions yet.') }}</p>
            @endforelse
        </x-ui.card>
    @endif

    <x-ui.modal name="assignment-form" :title="($form['id'] ?? null) ? __('Edit assignment') : __('New assignment')">
        <form wire:submit="save" id="assignment-form-el" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('Title')" wire:model="form.title" required class="sm:col-span-2" />
            <x-ui.textarea :label="__('Instructions')" wire:model="form.instructions" rows="3" optional class="sm:col-span-2" />
            <x-ui.input type="datetime-local" :label="__('Due')" wire:model="form.due_at" required />
            <x-ui.input type="number" step="0.01" min="1" :label="__('Maximum marks')" wire:model="form.max_marks" required />
            <x-ui.select :label="__('Counts towards')" wire:model="form.assessment_id" :options="$assessments" :placeholder="__('Nothing')" optional :hint="__('Marks are scaled into this assessment.')" class="sm:col-span-2" />
            <x-ui.switch wire:model="form.allow_late" :label="__('Accept late submissions')" class="sm:col-span-2" />
            <div class="sm:col-span-2"><x-ui.file-upload :label="__('Question file')" wire:model="attachment" :max-kb="20480" optional /></div>
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'assignment-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="assignment-form-el" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="evaluate" :title="__('Evaluate')" size="sm">
        <form wire:submit="evaluate" id="evaluate-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ $evaluation['student'] }}</p>
            <x-ui.input type="number" step="0.01" min="0" :label="__('Marks')" wire:model="evaluation.marks" required />
            <x-ui.textarea :label="__('Feedback')" wire:model="evaluation.feedback" rows="3" optional />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'evaluate')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="evaluate-form" loading="evaluate">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
