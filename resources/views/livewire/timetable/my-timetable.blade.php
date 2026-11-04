<div>
    <x-ui.page-header :title="__('My timetable')" :description="$data['week']['term']['name'] ?? null">
        @if ($data['as'] !== null)
            <x-slot:actions><x-ui.button variant="secondary" icon="calendar-plus" wire:click="calendarLink">{{ __('Add to my calendar') }}</x-ui.button></x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($calendarUrl)
        <x-ui.alert tone="info" class="mb-4" :title="__('Subscribe to your timetable')">
            <p>{{ __('In Google Calendar choose "Other calendars → From URL" (on a phone: add a subscribed calendar) and paste this private link. Classes update automatically.') }}</p>
            <div class="mt-2 flex items-center gap-2" x-data="{ copied: false }">
                <input type="text" readonly value="{{ $calendarUrl }}" class="min-w-0 flex-1 rounded-md border border-line bg-surface px-2 py-1 font-mono text-xs" x-ref="link" />
                <x-ui.button size="sm" variant="secondary" icon="copy" x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true"><span x-text="copied ? '{{ __('Copied') }}' : '{{ __('Copy') }}'"></span></x-ui.button>
            </div>
            <p class="mt-1 text-xs">{{ __('Keep this link private: anyone with it can see your timetable.') }}</p>
        </x-ui.alert>
    @endif

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
