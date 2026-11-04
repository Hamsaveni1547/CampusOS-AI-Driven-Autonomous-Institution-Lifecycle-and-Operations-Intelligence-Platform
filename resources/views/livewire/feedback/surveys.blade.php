<div>
    <x-ui.page-header :title="__('Feedback surveys')" :description="__('Answers are anonymous; results show only after enough responses.')">
        @canapi('feedback.surveys.manage')
            <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('New survey') }}</x-ui.button></x-slot:actions>
        @endcanapi
    </x-ui.page-header>

    <x-ui.table :empty="$surveys === []">
        <x-slot:head>
            <x-ui.th>{{ __('Survey') }}</x-ui.th>
            <x-ui.th>{{ __('Open') }}</x-ui.th>
            <x-ui.th>{{ __('Responses') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($surveys as $s)
            <x-ui.tr wire:key="sv-{{ $s['id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('surveys.results', $s['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $s['title'] }}</a>
                    <span class="block text-xs text-ink-muted">{{ $s['kind'] === 'course' ? __('Course feedback · :t', ['t' => $s['term']['name'] ?? '']) : __('General · :a', ['a' => \App\Support\Format::label($s['audience'])]) }}</span>
                </x-ui.td>
                <x-ui.td :label="__('Open')">{{ \App\Support\Format::date($s['opens_on']) }} – {{ \App\Support\Format::date($s['closes_on']) }}</x-ui.td>
                <x-ui.td :label="__('Responses')">{{ $s['responses'] }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$s['status']" /></x-ui.td>
                <x-ui.td class="text-right">
                    @canapi('feedback.surveys.manage')
                        @if ($s['status'] === 'draft')
                            <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="open('{{ $s['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>
                            <x-ui.button size="sm" icon="send" wire:click="setStatus('{{ $s['id'] }}', 'open')">{{ __('Open') }}</x-ui.button>
                        @elseif ($s['status'] === 'open')
                            <x-ui.button size="sm" variant="ghost" wire:click="setStatus('{{ $s['id'] }}', 'close')">{{ __('Close') }}</x-ui.button>
                        @else
                            <x-ui.button size="sm" variant="ghost" wire:click="setStatus('{{ $s['id'] }}', 'open')">{{ __('Reopen') }}</x-ui.button>
                        @endif
                    @endcanapi
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="message-square-heart" :title="__('No surveys yet')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.modal name="survey-form" :title="($form['id'] ?? '') ? __('Edit survey') : __('New survey')" size="lg">
        <form wire:submit="save" id="survey-form-el" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('Title')" wire:model="form.title" required class="sm:col-span-2" />
                <x-ui.select :label="__('Kind')" wire:model.live="form.kind" :options="['course' => __('Course feedback (per class)'), 'general' => __('General')]" required />
                @if (($form['kind'] ?? '') === 'course')
                    <x-ui.select :label="__('Term')" wire:model="form.term_id" :options="$terms" required />
                @else
                    <x-ui.select :label="__('Who answers')" wire:model="form.audience" :options="['students' => __('Students'), 'staff' => __('Staff'), 'all' => __('Everyone')]" required />
                @endif
                <x-ui.input type="date" :label="__('Opens on')" wire:model="form.opens_on" required />
                <x-ui.input type="date" :label="__('Closes on')" wire:model="form.closes_on" required />
            </div>
            <div class="space-y-2">
                <p class="text-sm font-medium text-ink">{{ __('Questions') }}</p>
                @foreach ($form['questions'] ?? [] as $i => $q)
                    <div wire:key="sq-{{ $i }}" class="grid items-end gap-2 rounded-md border border-line p-3 sm:grid-cols-[1fr_9rem_auto]">
                        <x-ui.input wire:model="form.questions.{{ $i }}.text" :placeholder="__('Question')" />
                        <x-ui.select wire:model.live="form.questions.{{ $i }}.type" :options="['rating' => __('Rating 1–5'), 'choice' => __('Choice'), 'text' => __('Comment')]" />
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeQuestion({{ $i }})"><span class="sr-only">{{ __('Remove') }}</span></x-ui.button>
                        @if ($q['type'] === 'choice')<x-ui.input wire:model="form.questions.{{ $i }}.options" :placeholder="__('Options, separated by commas')" class="sm:col-span-3" />@endif
                        <x-ui.checkbox wire:model="form.questions.{{ $i }}.required" :label="__('Required')" class="sm:col-span-3" />
                    </div>
                @endforeach
                <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="addQuestion">{{ __('Add question') }}</x-ui.button>
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'survey-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="survey-form-el" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
