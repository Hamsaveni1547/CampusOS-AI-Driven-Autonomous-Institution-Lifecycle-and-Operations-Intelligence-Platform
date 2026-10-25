@php($statuses = ['active' => __('Active'), 'graduated' => __('Graduated'), 'discontinued' => __('Discontinued')])
<div>
    <x-ui.page-header :title="__('Batches & sections')" :description="__('Each admission year of a programme is a batch, split into sections.')">
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('New batch') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters :active="$chips">
        <x-ui.select wire:model.live="filters.programme" :options="$programmes" :placeholder="__('All programmes')" class="sm:w-56" :aria-label="__('Programme')" />
        <x-ui.select wire:model.live="filters.department" :options="$departments" :placeholder="__('All departments')" class="sm:w-56" :aria-label="__('Department')" />
        <x-ui.input type="number" wire:model.live.debounce.500ms="filters.admission_year" :placeholder="__('Admission year')" class="sm:w-40" :aria-label="__('Admission year')" />
        <x-ui.select wire:model.live="filters.status" :options="$statuses" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="filters" :empty="$batches === []">
        <x-slot:head>
            <x-ui.th>{{ __('Batch') }}</x-ui.th>
            <x-ui.th>{{ __('Programme') }}</x-ui.th>
            <x-ui.th>{{ __('Years') }}</x-ui.th>
            <x-ui.th>{{ __('Sections') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($batches as $row)
            <x-ui.tr wire:key="b-{{ $row['id'] }}">
                <x-ui.td primary><button type="button" wire:click="openBatch('{{ $row['id'] }}')" class="font-medium text-ink hover:underline">{{ $row['name'] }}</button></x-ui.td>
                <x-ui.td :label="__('Programme')">{{ $row['programme']['name'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Years')">{{ $row['admission_year'] }}–{{ $row['graduation_year'] }}</x-ui.td>
                <x-ui.td :label="__('Sections')">
                    <div class="flex flex-wrap gap-1">
                        @forelse ($row['sections'] ?? [] as $section)
                            <x-ui.badge size="sm">{{ $section['name'] }}</x-ui.badge>
                        @empty
                            <span class="text-ink-subtle">—</span>
                        @endforelse
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$row['status']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="layers" :title="__('No batches found')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.modal name="batch-create" :title="__('New batch')">
        <form wire:submit="store" id="batch-create-form" class="space-y-4">
            <x-ui.combobox :label="__('Programme')" wire:model="create.programme_id" :options="$programmes" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('Admission year')" type="number" wire:model="create.admission_year" required />
                <x-ui.input :label="__('Name')" wire:model="create.name" optional :hint="__('Default: programme code and year.')" />
            </div>
            <x-ui.input :label="__('Sections')" wire:model="create.sections" optional :hint="__('Comma separated, e.g. A, B, C')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'batch-create')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="batch-create-form" loading="store">{{ __('Create') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.drawer name="batch-detail" size="lg" :title="$batch['name'] ?? ''" :description="isset($batch['programme']) ? $batch['programme']['name'].' · '.$batch['admission_year'].'–'.$batch['graduation_year'] : null">
        @if ($batch)
            <div class="space-y-6">
                @if ($canManage)
                    <form wire:submit="saveBatch" class="grid items-end gap-4 sm:grid-cols-3">
                        <x-ui.input :label="__('Name')" wire:model="batchForm.name" required />
                        <x-ui.select :label="__('Status')" wire:model="batchForm.status" :options="$statuses" />
                        <x-ui.button type="submit" variant="secondary" loading="saveBatch">{{ __('Save') }}</x-ui.button>
                    </form>
                @endif

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Sections') }}</h3>
                        @if ($canManage)<x-ui.button size="sm" icon="plus" variant="secondary" wire:click="editSection">{{ __('Add section') }}</x-ui.button>@endif
                    </div>
                    <div class="divide-y divide-line rounded-lg border border-line">
                        @forelse ($batch['sections'] ?? [] as $section)
                            <div wire:key="sec-{{ $section['id'] }}" class="flex items-center gap-3 px-4 py-3">
                                <span class="flex size-9 items-center justify-center rounded-md bg-primary-soft font-semibold text-primary-ink">{{ $section['name'] }}</span>
                                <div class="min-w-0 flex-1 text-sm">
                                    <p class="text-ink">{{ $section['class_advisor']['name'] ?? __('No class advisor') }}</p>
                                    <p class="text-xs text-ink-muted">{{ $section['capacity'] ? __('Capacity :n', ['n' => $section['capacity']]) : __('No capacity set') }}</p>
                                </div>
                                @if ($canManage)
                                    <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="editSection('{{ $section['id'] }}')" :aria-label="__('Edit')" />
                                    <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="askDeleteSection('{{ $section['id'] }}')" :aria-label="__('Remove')" />
                                @endif
                            </div>
                        @empty
                            <p class="px-4 py-6 text-center text-sm text-ink-muted">{{ __('No sections yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
        <x-slot:footer>
            @if ($canManage)
                <x-ui.button variant="ghost" class="mr-auto text-danger" x-on:click="$dispatch('open-dialog', 'delete-batch')">{{ __('Delete batch') }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'batch-detail')">{{ __('Close') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.modal name="section-form" :title="$sectionId ? __('Edit section') : __('Add section')" size="sm">
        <form wire:submit="saveSection" id="section-form" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('Name')" wire:model="sectionForm.name" required maxlength="20" />
                <x-ui.input :label="__('Capacity')" type="number" wire:model="sectionForm.capacity" optional />
            </div>
            <x-ui.combobox :label="__('Class advisor')" wire:model="sectionForm.class_advisor_id" :options="$advisors" optional />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'section-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="section-form" loading="saveSection">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-dialog name="delete-section" :title="__('Remove this section?')" :confirm-label="__('Remove')" action="deleteSection">
        {{ __('Only empty sections can be removed.') }}
    </x-ui.confirm-dialog>

    <x-ui.confirm-dialog name="delete-batch" :title="__('Delete this batch?')" :confirm-label="__('Delete')" action="deleteBatch">
        {{ __('Only batches without students can be deleted. Mark it discontinued instead to keep its history.') }}
    </x-ui.confirm-dialog>
</div>
