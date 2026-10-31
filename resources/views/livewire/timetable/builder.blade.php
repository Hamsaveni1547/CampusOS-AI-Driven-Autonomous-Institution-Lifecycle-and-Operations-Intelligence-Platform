<div>
    <x-ui.page-header :title="$timetable['section']['batch'].' · '.$timetable['section']['name']" :description="$timetable['term']['name'].' · '.$timetable['template']['name']" :back="route('timetables.index')" :back-label="__('Timetables')">
        <x-slot:meta>
            <x-ui.status-badge :status="$timetable['status']" />
            @if ($timetable['published_at'])<span class="text-xs text-ink-muted">v{{ $timetable['version'] }} · {{ __('by :name', ['name' => $timetable['published_by']]) }}</span>@endif
        </x-slot:meta>
        <x-slot:actions>
            @if ($can['publish'])
                @if ($timetable['status'] === 'published')
                    <x-ui.button variant="secondary" icon="pencil" wire:click="unpublish" wire:confirm="{{ __('Take back to draft? Students and teachers will not see it until it is published again.') }}">{{ __('Edit') }}</x-ui.button>
                @else
                    <x-ui.button icon="send" wire:click="publish" loading="publish">{{ __('Publish') }}</x-ui.button>
                @endif
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($timetable['clashes'] !== [])
        <x-ui.alert class="mb-6" tone="danger" :title="__('Clashes to fix before publishing')">
            @foreach ($timetable['clashes'] as $clash)
                <p>{{ $days[$clash['day_of_week']] ?? $clash['day_of_week'] }} {{ $clash['slot'] }}: {{ $clash['kind'] === 'teacher' ? __('teacher also booked for :with', ['with' => $clash['with']]) : __('room also booked for :with', ['with' => $clash['with']]) }}</p>
            @endforeach
        </x-ui.alert>
    @endif
    @if ($formError)<x-ui.alert class="mb-6" tone="danger">{{ $formError }}</x-ui.alert>@endif

    <div class="mb-6 overflow-x-auto rounded-lg border border-line bg-surface">
        <table class="w-full min-w-[720px] border-collapse text-sm">
            <thead>
                <tr class="bg-surface-muted/60">
                    <th class="w-20 border-b border-line px-3 py-2 text-left text-xs font-medium text-ink-muted"></th>
                    @foreach ($timetable['slots'] as $slot)
                        <th wire:key="h-{{ $slot['id'] }}" class="border-b border-l border-line px-2 py-2 text-left text-xs font-medium text-ink-muted">
                            {{ $slot['label'] }}<span class="block font-mono font-normal">{{ $slot['starts_at'] }}–{{ $slot['ends_at'] }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($days as $day => $dayLabel)
                    <tr wire:key="d-{{ $day }}">
                        <th class="border-b border-line px-3 py-2 text-left text-xs font-semibold text-ink">{{ $dayLabel }}</th>
                        @foreach ($timetable['slots'] as $slot)
                            @php($entry = $grid->get($day.'-'.$slot['id']))
                            <td wire:key="c-{{ $day }}-{{ $slot['id'] }}" @class(['h-16 border-b border-l border-line p-1 align-top', 'bg-surface-muted/50' => $slot['is_break']])>
                                @if ($slot['is_break'])
                                    <span class="block text-center text-xs text-ink-subtle">{{ $slot['label'] }}</span>
                                @elseif ($entry)
                                    <button type="button" @if ($can['manage']) wire:click="openCell({{ $day }}, '{{ $slot['id'] }}')" @endif
                                        @class(['h-full w-full rounded-md px-2 py-1 text-left', 'bg-primary-soft hover:ring-1 hover:ring-primary' => ! in_array($entry['id'], $clashIds, true), 'bg-danger-soft ring-1 ring-danger' => in_array($entry['id'], $clashIds, true), 'cursor-default' => ! $can['manage']])>
                                        <span class="block font-mono text-xs font-semibold text-primary-ink">{{ $entry['course']['code'] }}</span>
                                        <span class="block truncate text-xs text-ink">{{ $entry['teacher']['name'] ?? __('No teacher') }}</span>
                                        <span class="block text-[11px] text-ink-muted">{{ $entry['room']['code'] ?? '' }}@if ($entry['component'] !== 'theory') · {{ \App\Support\Format::label($entry['component']) }}@endif</span>
                                    </button>
                                @elseif ($can['manage'])
                                    <button type="button" wire:click="openCell({{ $day }}, '{{ $slot['id'] }}')" class="flex h-full w-full items-center justify-center rounded-md text-ink-subtle hover:bg-surface-muted hover:text-ink" aria-label="{{ __('Add class') }}"><x-ui.icon name="plus" /></button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-ui.card :title="__('Periods per week')" :description="__('Planned in this timetable compared with the hours allocated to each course.')" :padding="false">
        @forelse ($timetable['planned_hours'] as $row)
            <div wire:key="ph-{{ $row['class_id'] }}" class="flex items-center gap-3 border-b border-line px-5 py-2.5 text-sm last:border-0">
                <span class="min-w-0 flex-1 truncate text-ink">{{ $row['course'] }}</span>
                <span @class(['font-medium', 'text-success' => $row['planned'] === $row['allocated'] && $row['allocated'] > 0, 'text-warning' => $row['planned'] !== $row['allocated']])>{{ $row['planned'] }} / {{ $row['allocated'] }}</span>
            </div>
        @empty
            <p class="px-5 py-4 text-sm text-ink-muted">{{ __('No courses are offered to this section yet.') }}</p>
        @endforelse
    </x-ui.card>

    @if ($can['manage'])
        <x-ui.modal name="cell-form" :title="($days[$cell['day_of_week']] ?? '').' · '.(collect($timetable['slots'])->firstWhere('id', $cell['time_slot_id'])['label'] ?? '')" size="sm">
            <form wire:submit="saveCell" id="cell-form" class="space-y-4">
                <x-ui.select :label="__('Course')" wire:model.live="cell.offering_section_id" :options="$classOptions" :placeholder="__('Choose')" required />
                <x-ui.select :label="__('Teacher')" wire:model="cell.employee_id" :options="$teacherOptions" :placeholder="__('Allocated teacher')" optional />
                <x-ui.combobox :label="__('Room')" wire:model="cell.room_id" :options="$rooms" optional />
                <x-ui.select :label="__('Part')" wire:model="cell.component" :options="['theory' => __('Theory'), 'lab' => __('Lab'), 'tutorial' => __('Tutorial')]" />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                @if ($entryId)<x-ui.button variant="ghost" class="mr-auto text-danger" wire:click="clearCell" loading="clearCell">{{ __('Clear') }}</x-ui.button>@endif
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'cell-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="cell-form" loading="saveCell">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
