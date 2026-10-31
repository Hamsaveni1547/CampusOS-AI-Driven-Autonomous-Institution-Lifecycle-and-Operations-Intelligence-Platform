<div>
    <x-ui.page-header :title="__('My timetable')" :description="$data['week']['term']['name'] ?? null" />

    @if ($data['as'] === null)
        <x-ui.empty-state icon="calendar-days" :title="__('No timetable for you')" :description="__('Timetables appear for students placed in a section and for teaching staff.')" />
    @else
        <x-ui.card class="mb-6" :padding="false">
            <div class="flex items-center justify-between border-b border-line px-5 py-3">
                <x-ui.button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftDay(-1)" :aria-label="__('Previous day')" />
                <h2 class="text-sm font-semibold text-ink">{{ $dayLabel }}</h2>
                <x-ui.button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftDay(1)" :aria-label="__('Next day')" />
            </div>
            <x-timetable.day :periods="$data['today']" />
        </x-ui.card>

        <h2 class="mb-3 text-base font-semibold text-ink">{{ __('Week') }}</h2>
        <x-timetable.week :entries="$data['week']['entries'] ?? []" :show="$data['as'] === 'student' ? 'section' : 'teacher'" />
    @endif
</div>
