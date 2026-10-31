<div>
    <x-ui.page-header :title="($curriculum['programme']['name'] ?? '').' · '.$curriculum['regulation']" :description="__('Version :v', ['v' => $curriculum['version']]).($curriculum['approved_by'] ? ' · '.__('approved by :name on :date', ['name' => $curriculum['approved_by'], 'date' => \App\Support\Format::date($curriculum['approved_at'])]) : '')" :back="route('curricula.index')" :back-label="__('Curricula')">
        <x-slot:meta><x-ui.status-badge :status="$curriculum['status']" :label="$statuses[$curriculum['status']] ?? null" /></x-slot:meta>
        <x-slot:actions>
            @if ($can['edit'])
                <x-ui.button variant="ghost" wire:click="deleteDraft" wire:confirm="{{ __('Delete this draft?') }}">{{ __('Delete draft') }}</x-ui.button>
                <x-ui.button variant="secondary" icon="plus" wire:click="openAdd">{{ __('Add course') }}</x-ui.button>
            @endif
            @if ($can['submit'])<x-ui.button icon="send" wire:click="submit" loading="submit">{{ __('Send for approval') }}</x-ui.button>@endif
            @if ($can['decide'])
                <x-ui.button variant="secondary" wire:click="openDecision(false)">{{ __('Return for changes') }}</x-ui.button>
                <x-ui.button icon="circle-check" wire:click="openDecision(true)">{{ __('Approve') }}</x-ui.button>
            @endif
            @if ($can['offer'])<x-ui.button icon="layers" :href="route('course-offerings.index', ['generate' => $curriculum['id']])">{{ __('Offer in a term') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($curriculum['remarks'] && $curriculum['status'] === 'draft')
        <x-ui.alert class="mb-6" tone="warning" :title="__('Returned for changes')">{{ $curriculum['remarks'] }}</x-ui.alert>
    @endif

    <div class="space-y-6">
        @forelse ($terms as $termNumber => $entries)
            <x-ui.card wire:key="term-{{ $termNumber }}" :title="__('Term :n', ['n' => $termNumber])" :description="__(':c credits', ['c' => $curriculum['credits_by_term'][$termNumber] ?? 0])" :padding="false">
                @if ($can['edit'])
                    <x-slot:actions><x-ui.button size="sm" variant="ghost" icon="plus" wire:click="openAdd({{ $termNumber }})" :aria-label="__('Add course to this term')" /></x-slot:actions>
                @endif
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>{{ __('Course') }}</x-ui.th>
                        <x-ui.th>{{ __('Category') }}</x-ui.th>
                        <x-ui.th>{{ __('Credits') }}</x-ui.th>
                        <x-ui.th>{{ __('L-T-P') }}</x-ui.th>
                        <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
                    </x-slot:head>
                    @foreach ($entries as $entry)
                        <x-ui.tr wire:key="e-{{ $entry['course']['id'] }}">
                            <x-ui.td primary>
                                <a href="{{ route('courses.show', $entry['course']['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $entry['course']['title'] }}</a>
                                <span class="block font-mono text-xs text-ink-muted">{{ $entry['course']['code'] }}</span>
                            </x-ui.td>
                            <x-ui.td :label="__('Category')">
                                {{ $categories[$entry['category']] ?? $entry['category'] }}
                                @if ($entry['is_elective'])<x-ui.badge size="sm" tone="info">{{ __('Elective') }}</x-ui.badge>@endif
                            </x-ui.td>
                            <x-ui.td :label="__('Credits')">{{ $entry['course']['credits'] }}</x-ui.td>
                            <x-ui.td :label="__('L-T-P')"><span class="font-mono text-xs">{{ $entry['course']['ltp'] }}</span></x-ui.td>
                            <x-ui.td class="text-right">
                                @if ($can['edit'])
                                    <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeEntry('{{ $entry['course']['id'] }}')" :aria-label="__('Remove')" />
                                @endif
                            </x-ui.td>
                        </x-ui.tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="layers" :title="__('No courses yet')" :description="$can['edit'] ? __('Add courses term by term.') : null" />
        @endforelse
    </div>

    @if ($can['edit'])
        <x-ui.modal name="entry-form" :title="__('Add course')">
            <form wire:submit="addEntry" id="entry-form" class="grid gap-4 sm:grid-cols-2">
                <x-ui.combobox :label="__('Course')" wire:model="entry.course_id" :options="$courses" required class="sm:col-span-2" />
                <x-ui.input :label="__('Term')" type="number" min="1" :max="$curriculum['total_terms']" wire:model="entry.term_number" required />
                <x-ui.select :label="__('Category')" wire:model="entry.category" :options="$categories" />
                <x-ui.checkbox wire:model="entry.is_elective" :label="__('Elective (students choose)')" class="sm:col-span-2" />
                @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'entry-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="entry-form" loading="addEntry">{{ __('Add') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($can['decide'])
        <x-ui.modal name="curriculum-approve" :title="__('Approve this curriculum?')" size="sm">
            <p class="text-sm text-ink-muted">{{ __('It becomes the version in use. An earlier approved version of the same regulation is archived.') }}</p>
            <div class="mt-4"><x-ui.input :label="__('Note')" wire:model="remarks" optional /></div>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'curriculum-approve')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button wire:click="decide(true)" loading="decide">{{ __('Approve') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
        <x-ui.modal name="curriculum-return" :title="__('Return for changes')" size="sm">
            <x-ui.textarea :label="__('What needs to change?')" wire:model="remarks" rows="3" required />
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'curriculum-return')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button variant="danger" wire:click="decide(false)" loading="decide">{{ __('Return') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
