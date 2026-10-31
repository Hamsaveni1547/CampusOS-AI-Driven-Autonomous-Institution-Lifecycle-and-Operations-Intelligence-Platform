<div>
    <x-ui.page-header :title="__('Question bank')" :description="__('Questions are stored encrypted. Tag them well so papers can be generated from blueprints.')">
        @if ($course !== '')
            @canapi('qbank.questions.contribute')
                <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Add question') }}</x-ui.button></x-slot:actions>
            @endcanapi
        @endif
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap gap-3">
        <div class="w-72"><x-ui.select wire:model.live="course" :options="$courses" :placeholder="__('Choose a course…')" /></div>
        @if ($course !== '')
            <div class="w-40"><x-ui.select wire:model.live="filters.type" :options="$types" :placeholder="__('Any type')" /></div>
            <div class="w-28"><x-ui.input type="number" min="1" wire:model.live.debounce.400ms="filters.unit" :placeholder="__('Unit')" /></div>
            <div class="w-36"><x-ui.select wire:model.live="filters.difficulty" :options="$difficulties" :placeholder="__('Any difficulty')" /></div>
            <div class="w-36"><x-ui.select wire:model.live="filters.status" :options="['active' => __('Active'), 'retired' => __('Retired')]" /></div>
        @endif
    </div>

    @if ($course === '')
        <x-ui.empty-state icon="library" :title="__('Choose a course')" :description="__('Pick a course to see and add its questions.')" />
    @else
        <div class="mb-4 flex flex-wrap gap-2 text-sm">
            <x-ui.badge>{{ __(':n active questions', ['n' => $coverage['total']]) }}</x-ui.badge>
            @foreach ($coverage['by_unit'] as $unit => $count)<x-ui.badge wire:key="cu-{{ $unit }}" size="sm">{{ __('Unit :u: :n', ['u' => $unit, 'n' => $count]) }}</x-ui.badge>@endforeach
            @foreach ($coverage['by_type'] as $type => $count)<x-ui.badge wire:key="ct-{{ $type }}" size="sm" tone="info">{{ $type }}: {{ $count }}</x-ui.badge>@endforeach
        </div>

        <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
            <x-slot:head>
                <x-ui.th>{{ __('Question') }}</x-ui.th>
                <x-ui.th>{{ __('Type') }}</x-ui.th>
                <x-ui.th>{{ __('Tags') }}</x-ui.th>
                <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
            </x-slot:head>
            @foreach ($rows->items as $row)
                <x-ui.tr wire:key="q-{{ $row['id'] }}">
                    <x-ui.td primary>
                        <span class="whitespace-pre-line text-ink">{{ $row['text'] }}</span>
                        @if ($row['options'])<span class="mt-1 block text-xs text-ink-muted">{{ implode(' · ', $row['options']) }}</span>@endif
                    </x-ui.td>
                    <x-ui.td :label="__('Type')">{{ $types[$row['type']] ?? $row['type'] }}<span class="block text-xs text-ink-muted">{{ __(':m marks', ['m' => $row['marks'] + 0]) }}</span></x-ui.td>
                    <x-ui.td :label="__('Tags')">
                        <span class="text-xs text-ink-muted">@if ($row['unit']){{ __('Unit :u', ['u' => $row['unit']]) }} · @endif{{ $difficulties[$row['difficulty']] ?? $row['difficulty'] }}@if ($row['bloom_level']) · {{ $blooms[$row['bloom_level']] ?? $row['bloom_level'] }}@endif @if ($row['co_code']) · {{ $row['co_code'] }}@endif</span>
                    </x-ui.td>
                    <x-ui.td class="text-right">
                        @if ($row['can_edit'])
                            <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="edit('{{ $row['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>
                            <x-ui.button size="sm" variant="ghost" wire:click="retire('{{ $row['id'] }}', {{ $row['status'] === 'active' ? 'true' : 'false' }})">{{ $row['status'] === 'active' ? __('Retire') : __('Restore') }}</x-ui.button>
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="file-question" :title="__('No questions')" /></x-slot:emptyState>
            <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
        </x-ui.table>
    @endif

    <x-ui.modal name="question-form" :title="($form['id'] ?? null) ? __('Edit question') : __('Add question')" size="lg">
        <form wire:submit="save" id="question-form-el" class="grid gap-4 sm:grid-cols-3">
            <x-ui.textarea :label="__('Question')" wire:model="form.text" rows="4" required class="sm:col-span-3" />
            <x-ui.select :label="__('Type')" wire:model.live="form.type" :options="$types" required />
            <x-ui.input type="number" step="0.5" min="0.5" :label="__('Marks')" wire:model="form.marks" required />
            <x-ui.select :label="__('Difficulty')" wire:model="form.difficulty" :options="$difficulties" required />
            <x-ui.input type="number" min="1" :label="__('Unit')" wire:model="form.unit" optional />
            <x-ui.select :label="__('Bloom level')" wire:model="form.bloom_level" :options="$blooms" :placeholder="__('Not set')" optional />
            <x-ui.input :label="__('Course outcome')" wire:model="form.co_code" placeholder="CO1" optional />
            @if (($form['type'] ?? '') === 'mcq')
                <div class="grid gap-2 sm:col-span-3 sm:grid-cols-2">
                    @foreach (range(0, 3) as $i)
                        <x-ui.input wire:key="opt-{{ $i }}" :label="__('Option :n', ['n' => $i + 1])" wire:model="form.options.{{ $i }}" />
                    @endforeach
                </div>
            @endif
            <x-ui.textarea :label="__('Answer / scheme')" wire:model="form.answer" rows="2" optional class="sm:col-span-3" :hint="__('Hidden when a sealed paper is released.')" />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-3">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'question-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="question-form-el" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
