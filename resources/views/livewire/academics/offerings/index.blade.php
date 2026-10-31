<div>
    <x-ui.page-header :title="__('Course offerings')" :description="__('Courses taught this term, the sections taking them and their teachers.')">
        <x-slot:actions>
            @canapi('academics.catalogue.view')
                <x-ui.button variant="secondary" icon="gauge" :href="route('faculty-workload.index', ['term' => $termId])">{{ __('Workload') }}</x-ui.button>
            @endcanapi
            @if ($can['manage'])
                <x-ui.button variant="secondary" icon="layers" wire:click="openGenerate">{{ __('From curriculum') }}</x-ui.button>
                <x-ui.button icon="plus" wire:click="openOffering">{{ __('Offer a course') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <x-ui.select wire:model.live="term" :options="$terms" :placeholder="__('Current term')" class="sm:w-64" :aria-label="__('Term')" />
        <x-ui.input wire:model.live.debounce.400ms="search" icon="search" :placeholder="__('Course code or title…')" class="sm:w-64" :aria-label="__('Search')" />
        <x-ui.switch wire:model.live="unallocated" :label="__('Only classes without a teacher')" />
    </div>

    @if ($terms === [])
        <x-ui.empty-state icon="calendar-check" :title="__('No terms yet')" :description="__('Create the academic year and its terms first.')" />
    @endif

    <div class="space-y-4" wire:loading.class="opacity-60" wire:target="term,search,unallocated">
        @forelse ($offerings as $offering)
            <x-ui.card wire:key="of-{{ $offering['id'] }}" :padding="false">
                <div class="flex flex-wrap items-start gap-3 border-b border-line px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('courses.show', $offering['course']['id']) }}" wire:navigate class="font-semibold text-ink hover:underline">{{ $offering['course']['title'] }}</a>
                        <p class="text-xs text-ink-muted">
                            <span class="font-mono">{{ $offering['course']['code'] }}</span> · {{ __(':c credits', ['c' => $offering['course']['credits']]) }} · {{ $offering['course']['ltp'] }}
                            @if ($offering['is_elective']) · <x-ui.badge size="sm" tone="info">{{ __('Elective') }}</x-ui.badge>@endif
                        </p>
                    </div>
                    @if ($can['manage'])
                        <x-ui.dropdown>
                            <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                            <x-ui.dropdown-item icon="pencil" wire:click="openOffering('{{ $offering['id'] }}')">{{ __('Edit sections') }}</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="removeOffering('{{ $offering['id'] }}')" wire:confirm="{{ __('Remove this offering?') }}">{{ __('Remove') }}</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    @endif
                </div>
                @forelse ($offering['sections'] as $class)
                    <div wire:key="cl-{{ $class['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <a href="{{ route('classes.show', $class['id']) }}" wire:navigate class="w-40 shrink-0 text-sm font-medium text-ink hover:underline">{{ $class['section']['batch'] }} · {{ $class['section']['name'] }}</a>
                        <div class="flex min-w-0 flex-1 flex-wrap gap-1.5">
                            @forelse ($class['faculty'] as $teacher)
                                <span wire:key="al-{{ $teacher['id'] }}" class="inline-flex items-center gap-1 rounded-full bg-surface-muted px-2.5 py-0.5 text-xs text-ink">
                                    {{ $teacher['name'] }} · {{ $components[$teacher['component']] ?? $teacher['component'] }} · {{ $teacher['weekly_hours'] }}h
                                    @if ($can['allocate'])
                                        <button type="button" wire:click="deallocate('{{ $teacher['id'] }}')" wire:confirm="{{ __('Remove this teacher from the class?') }}" class="text-ink-subtle hover:text-danger" aria-label="{{ __('Remove') }}"><x-ui.icon name="x" class="size-3" /></button>
                                    @endif
                                </span>
                            @empty
                                <x-ui.badge size="sm" tone="warning">{{ __('No teacher') }}</x-ui.badge>
                            @endforelse
                        </div>
                        <div class="w-28"><x-ui.progress :value="$class['coverage_percent'] ?? 0" show-value /></div>
                        @if ($can['allocate'])
                            <x-ui.button size="sm" variant="ghost" icon="user-plus" wire:click="openAllocate('{{ $class['id'] }}')" :aria-label="__('Allocate teacher')" />
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No sections yet.') }}</p>
                @endforelse
            </x-ui.card>
        @empty
            @if ($terms !== [])
                <x-ui.empty-state icon="layers" :title="__('Nothing offered in this term yet')" :description="$can['manage'] ? __('Offer courses one by one or all at once from an approved curriculum.') : null" />
            @endif
        @endforelse
    </div>

    @if ($can['manage'])
        <x-ui.modal name="offering-form" :title="$editingOfferingId ? __('Edit offering') : __('Offer a course')">
            <form wire:submit="saveOffering" id="offering-form" class="space-y-4">
                @unless ($editingOfferingId)
                    <x-ui.combobox :label="__('Course')" wire:model="offeringForm.course_id" :options="$courses" required />
                @endunless
                <x-ui.combobox :label="__('Sections')" wire:model="offeringForm.section_ids" :options="$sections" multiple optional />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Capacity')" type="number" min="1" wire:model="offeringForm.capacity" optional :hint="__('For electives.')" />
                    <x-ui.checkbox wire:model="offeringForm.is_elective" :label="__('Elective')" class="self-end" />
                </div>
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'offering-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="offering-form" loading="saveOffering">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal name="generate-form" :title="__('Offer from a curriculum')" :description="__('Offers every course of one curriculum term to the chosen sections in this term.')">
            <form wire:submit="generate" id="generate-form" class="space-y-4">
                <x-ui.select :label="__('Approved curriculum')" wire:model="generateForm.curriculum_id" :options="$curricula" :placeholder="__('Choose')" required />
                <x-ui.input :label="__('Curriculum term')" type="number" min="1" wire:model="generateForm.term_number" required :hint="__('e.g. 5 for the fifth semester')" />
                <x-ui.combobox :label="__('Sections')" wire:model="generateForm.section_ids" :options="$sections" multiple required />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'generate-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="generate-form" loading="generate">{{ __('Offer courses') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($can['allocate'])
        <x-ui.modal name="allocate-form" :title="__('Allocate a teacher')" size="sm">
            <form wire:submit="allocate" id="allocate-form" class="space-y-4">
                <x-ui.combobox :label="__('Teacher')" wire:model="allocation.employee_id" :options="$teachers" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select :label="__('Part')" wire:model="allocation.component" :options="$components" required />
                    <x-ui.input :label="__('Hours / week')" type="number" min="1" max="30" wire:model="allocation.weekly_hours" required />
                </div>
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'allocate-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="allocate-form" loading="allocate">{{ __('Allocate') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
