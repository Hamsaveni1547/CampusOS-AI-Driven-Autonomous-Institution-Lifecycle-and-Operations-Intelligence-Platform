@php($actionLabels = ['make-current' => __('Make current'), 'close' => __('Close'), 'delete' => __('Delete')])
<div>
    <x-ui.page-header :title="__('Academic years')" :description="__('Years, semesters and the institution calendar.')">
        <x-slot:actions>
            @if ($canManage)
                @if ($tab === 'years')
                    <x-ui.button icon="plus" wire:click="editYear">{{ __('New academic year') }}</x-ui.button>
                @else
                    <x-ui.button icon="plus" wire:click="editEvent">{{ __('Add to calendar') }}</x-ui.button>
                @endif
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 flex gap-5 border-b border-line">
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'years')" :active="$tab === 'years'">{{ __('Years & terms') }}</x-ui.tab-link>
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'events')" :active="$tab === 'events'">{{ __('Holidays & events') }}</x-ui.tab-link>
    </div>

    @if ($tab === 'years')
        <div class="space-y-4">
            @forelse ($years as $year)
                <x-ui.card wire:key="y-{{ $year['id'] }}" :padding="false">
                    <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                                {{ $year['name'] }}
                                @if ($year['is_current'] ?? false)<x-ui.badge size="sm" tone="success" dot>{{ __('Current') }}</x-ui.badge>@endif
                                <x-ui.status-badge :status="$year['status']" size="sm" />
                            </h2>
                            <p class="text-sm text-ink-muted">{{ \App\Support\Format::date($year['starts_on']) }} – {{ \App\Support\Format::date($year['ends_on']) }}</p>
                        </div>
                        @if ($canManage && $year['status'] !== 'closed')
                            <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="editTerm('{{ $year['id'] }}')">{{ __('Add term') }}</x-ui.button>
                            <x-ui.dropdown>
                                <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                                <x-ui.dropdown-item icon="pencil" wire:click="editYear('{{ $year['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                                @unless ($year['is_current'] ?? false)
                                    <x-ui.dropdown-item icon="star" wire:click="confirm('year', '{{ $year['id'] }}', 'make-current')">{{ __('Make current') }}</x-ui.dropdown-item>
                                @endunless
                                <x-ui.dropdown-item icon="lock" wire:click="confirm('year', '{{ $year['id'] }}', 'close')">{{ __('Close year') }}</x-ui.dropdown-item>
                                <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="confirm('year', '{{ $year['id'] }}', 'delete')">{{ __('Delete') }}</x-ui.dropdown-item>
                            </x-ui.dropdown>
                        @endif
                    </div>
                    @forelse ($year['terms'] ?? [] as $term)
                        <div wire:key="t-{{ $term['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-2 text-sm font-medium text-ink">
                                    {{ $term['name'] }}
                                    @if ($term['is_current'] ?? false)<x-ui.badge size="sm" tone="success" dot>{{ __('Current') }}</x-ui.badge>@endif
                                </p>
                                <p class="text-xs text-ink-muted">{{ $termTypes[$term['type']] ?? $term['type'] }} · {{ \App\Support\Format::date($term['starts_on']) }} – {{ \App\Support\Format::date($term['ends_on']) }}</p>
                            </div>
                            <x-ui.status-badge :status="$term['status']" size="sm" />
                            @if ($canManage && $term['status'] !== 'closed')
                                <x-ui.dropdown>
                                    <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                                    <x-ui.dropdown-item icon="pencil" wire:click="editTerm('{{ $year['id'] }}', '{{ $term['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                                    @unless ($term['is_current'] ?? false)
                                        <x-ui.dropdown-item icon="star" wire:click="confirm('term', '{{ $term['id'] }}', 'make-current')">{{ __('Make current') }}</x-ui.dropdown-item>
                                    @endunless
                                    <x-ui.dropdown-item icon="lock" wire:click="confirm('term', '{{ $term['id'] }}', 'close')">{{ __('Close term') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="confirm('term', '{{ $term['id'] }}', 'delete')">{{ __('Delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-ink-muted">{{ __('No terms yet.') }}</p>
                    @endforelse
                </x-ui.card>
            @empty
                <x-ui.empty-state icon="calendar-check" :title="__('No academic years yet')" :description="__('Create the current academic year to get started.')" />
            @endforelse
        </div>
    @else
        <x-ui.card :padding="false">
            <div class="flex items-center justify-between border-b border-line px-5 py-3">
                <x-ui.button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftMonth(-1)" :aria-label="__('Previous month')" />
                <h2 class="text-sm font-semibold text-ink">{{ $monthStart->translatedFormat('F Y') }}</h2>
                <x-ui.button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftMonth(1)" :aria-label="__('Next month')" />
            </div>
            @forelse ($events as $event)
                <div wire:key="ev-{{ $event['id'] }}" class="flex items-start gap-4 border-b border-line px-5 py-3 last:border-0">
                    <div class="w-24 shrink-0 text-sm">
                        <p class="font-medium text-ink">{{ \App\Support\Format::date($event['starts_on'], 'd M') }}</p>
                        @if ($event['ends_on'] !== $event['starts_on'])<p class="text-xs text-ink-muted">{{ __('to :date', ['date' => \App\Support\Format::date($event['ends_on'], 'd M')]) }}</p>@endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-ink">
                            @if ($canManage)
                                <button type="button" wire:click="editEvent('{{ $event['id'] }}')" class="hover:underline">{{ $event['title'] }}</button>
                            @else
                                {{ $event['title'] }}
                            @endif
                        </p>
                        @if ($event['description'])<p class="text-xs text-ink-muted">{{ $event['description'] }}</p>@endif
                    </div>
                    <div class="flex flex-wrap justify-end gap-1">
                        <x-ui.badge size="sm" :tone="$event['is_holiday'] ? 'danger' : 'info'">{{ $eventTypes[$event['type']] ?? $event['type'] }}</x-ui.badge>
                        @if ($event['audience'] !== 'all')<x-ui.badge size="sm">{{ \App\Support\Format::label($event['audience']) }}</x-ui.badge>@endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-ink-muted">{{ __('Nothing on the calendar this month.') }}</p>
            @endforelse
        </x-ui.card>
    @endif

    <x-ui.modal name="year-form" :title="$yearId ? __('Edit academic year') : __('New academic year')">
        <form wire:submit="saveYear" id="year-form" class="space-y-4">
            <x-ui.input :label="__('Name')" wire:model="yearForm.name" required :hint="__('e.g. 2026-27')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.date-picker :label="__('Starts')" wire:model="yearForm.starts_on" />
                <x-ui.date-picker :label="__('Ends')" wire:model="yearForm.ends_on" />
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'year-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="year-form" loading="saveYear">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="term-form" :title="$termId ? __('Edit term') : __('Add term')">
        <form wire:submit="saveTerm" id="term-form" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('Name')" wire:model="termForm.name" required :hint="__('e.g. Odd Semester 2026')" />
                <x-ui.select :label="__('Type')" wire:model="termForm.type" :options="$termTypes" :disabled="$termId !== null" />
                <x-ui.date-picker :label="__('Starts')" wire:model="termForm.starts_on" />
                <x-ui.date-picker :label="__('Ends')" wire:model="termForm.ends_on" />
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'term-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="term-form" loading="saveTerm">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.drawer name="event-form" :title="$eventId ? __('Edit calendar entry') : __('Add to calendar')">
        <form wire:submit="saveEvent" id="event-form" class="space-y-5">
            <x-ui.input :label="__('Title')" wire:model="eventForm.title" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('Type')" wire:model.live="eventForm.type" :options="$eventTypes" />
                <x-ui.select :label="__('For')" wire:model="eventForm.audience" :options="['all' => __('Everyone'), 'students' => __('Students'), 'staff' => __('Staff')]" />
                <x-ui.date-picker :label="__('From')" wire:model="eventForm.starts_on" />
                <x-ui.date-picker :label="__('To')" wire:model="eventForm.ends_on" optional :hint="__('Leave empty for one day.')" />
            </div>
            @if ($eventForm['type'] !== 'holiday')
                <x-ui.switch wire:model="eventForm.is_holiday" :label="__('Institution closed')" />
            @endif
            <x-ui.textarea :label="__('Description')" wire:model="eventForm.description" optional />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            @if ($eventId)
                <x-ui.button variant="ghost" class="mr-auto text-danger" wire:click="deleteEvent" wire:confirm="{{ __('Remove this calendar entry?') }}" loading="deleteEvent">{{ __('Remove') }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'event-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="event-form" loading="saveEvent">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.confirm-dialog name="period-action" :title="$pending ? $actionLabels[$pending['action']].'?' : ''" :confirm-label="$pending ? $actionLabels[$pending['action']] : ''" :tone="($pending['action'] ?? '') === 'make-current' ? 'primary' : 'danger'" action="runPending">
        @switch($pending['action'] ?? null)
            @case('make-current'){{ __('It becomes the default everywhere, such as attendance and fees.') }}@break
            @case('close'){{ __('A closed period can no longer be changed.') }}@break
            @default{{ __('Only periods that nothing refers to can be deleted.') }}
        @endswitch
    </x-ui.confirm-dialog>
</div>
