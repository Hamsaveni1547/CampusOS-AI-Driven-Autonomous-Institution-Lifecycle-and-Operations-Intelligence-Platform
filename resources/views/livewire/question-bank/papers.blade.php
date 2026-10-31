<div>
    <x-ui.page-header :title="__('Question papers')" :description="__('Blueprints, generated sets and the review → seal → release flow.')" />

    <div class="mb-6 w-72"><x-ui.select wire:model.live="course" :options="$courses" :placeholder="__('All courses')" /></div>

    @if ($course !== '' && ($canSet || $blueprints !== []))
        <x-ui.card class="mb-6" :title="__('Blueprints')" :padding="false">
            @if ($canSet)
                <x-slot:actions><x-ui.button size="sm" icon="plus" wire:click="openBlueprint">{{ __('New blueprint') }}</x-ui.button></x-slot:actions>
            @endif
            @forelse ($blueprints as $bp)
                <div wire:key="bp-{{ $bp['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                    <span class="min-w-0 flex-1">
                        <span class="font-medium text-ink">{{ $bp['name'] }}</span>
                        <span class="block text-xs text-ink-muted">{{ __(':m marks · :d min', ['m' => $bp['total_marks'] + 0, 'd' => $bp['duration_minutes']]) }} · @foreach ($bp['sections'] as $s){{ $s['name'] }} ({{ $s['attempt'] ? $s['attempt'].'/' : '' }}{{ $s['count'] }} × {{ $s['marks'] + 0 }})@if (! $loop->last), @endif @endforeach</span>
                    </span>
                    @if ($canSet)
                        <x-ui.button size="sm" icon="sparkles" wire:click="openGenerate('{{ $bp['id'] }}')">{{ __('Generate') }}</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="openBlueprint('{{ $bp['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="deleteBlueprint('{{ $bp['id'] }}')" wire:confirm="{{ __('Delete this blueprint?') }}"><span class="sr-only">{{ __('Delete') }}</span></x-ui.button>
                    @endif
                </div>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No blueprints yet. A blueprint sets the parts, question types and marks of a paper.') }}</p>
            @endforelse
        </x-ui.card>
    @endif

    <div class="mb-4 w-48"><x-ui.select wire:model.live="filters.status" :options="$statuses" :placeholder="__('Any status')" /></div>
    <x-ui.table loading="filters,gotoPage,course" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Paper') }}</x-ui.th>
            <x-ui.th>{{ __('Exam') }}</x-ui.th>
            <x-ui.th>{{ __('Set by') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $row)
            <x-ui.tr wire:key="qp-{{ $row['id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('question-papers.show', $row['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $row['course']['code'] }} · {{ __('Set :s', ['s' => $row['set_code']]) }}</a>
                    <span class="block text-xs text-ink-muted">{{ $row['blueprint']['name'] }}</span>
                </x-ui.td>
                <x-ui.td :label="__('Exam')">@if ($row['exam']){{ $row['exam']['cycle'] }}<span class="block text-xs text-ink-muted">{{ \App\Support\Format::date($row['exam']['date']) }} {{ $row['exam']['starts_at'] }}</span>@else<span class="text-ink-muted">{{ __('Not linked') }}</span>@endif</x-ui.td>
                <x-ui.td :label="__('Set by')">{{ $row['set_by'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$row['status']" :label="$statuses[$row['status']] ?? null" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="file-lock" :title="__('No question papers')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="blueprint-form" :title="($blueprint['id'] ?? null) ? __('Edit blueprint') : __('New blueprint')" size="lg">
        <form wire:submit="saveBlueprint" id="blueprint-form-el" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input :label="__('Name')" wire:model="blueprint.name" required :hint="__('e.g. End-term 60 marks')" />
                <x-ui.input type="number" min="15" :label="__('Duration (minutes)')" wire:model="blueprint.duration_minutes" required />
                <x-ui.input type="number" min="0" :label="__('Avoid questions of last N papers')" wire:model="blueprint.avoid_recent_papers" required />
            </div>
            @foreach ($blueprint['sections'] ?? [] as $i => $section)
                <div wire:key="sec-{{ $i }}" class="grid items-end gap-2 rounded-md border border-line p-3 sm:grid-cols-[1fr_9rem_5rem_5rem_5rem_auto]">
                    <x-ui.input :label="__('Part')" wire:model="blueprint.sections.{{ $i }}.name" required />
                    <x-ui.select :label="__('Type')" wire:model="blueprint.sections.{{ $i }}.type" :options="$types" required />
                    <x-ui.input type="number" step="0.5" min="0.5" :label="__('Marks')" wire:model="blueprint.sections.{{ $i }}.marks" required />
                    <x-ui.input type="number" min="1" :label="__('Questions')" wire:model="blueprint.sections.{{ $i }}.count" required />
                    <x-ui.input type="number" min="1" :label="__('Attempt')" wire:model="blueprint.sections.{{ $i }}.attempt" optional />
                    <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeSection({{ $i }})"><span class="sr-only">{{ __('Remove') }}</span></x-ui.button>
                </div>
            @endforeach
            <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="addSection">{{ __('Add part') }}</x-ui.button>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'blueprint-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="blueprint-form-el" loading="saveBlueprint">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="generate" :title="__('Generate papers')" size="sm">
        <form wire:submit="generate" id="generate-form" class="space-y-4">
            <x-ui.select :label="__('Sets')" wire:model="generation.sets" :options="[1 => '1', 2 => '2', 3 => '3', 4 => '4']" required />
            <x-ui.select :label="__('For exam')" wire:model="generation.exam_schedule_id" :options="$exams" :placeholder="__('Not linked yet')" optional :hint="__('A sealed paper opens only before its exam.')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'generate')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="generate-form" loading="generate">{{ __('Generate') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
