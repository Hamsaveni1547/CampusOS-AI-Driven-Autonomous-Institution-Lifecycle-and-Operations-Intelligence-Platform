<div>
    <x-ui.page-header :title="__('Timetable')" :description="__('Published timetables by section, teacher or room, and changes for a day.')" :back="route('timetables.index')" :back-label="__('Timetables')" />

    <div class="mb-6 flex flex-wrap items-end gap-3">
        <x-ui.select wire:model.live="by" :options="['section' => __('Section'), 'teacher' => __('Teacher'), 'room' => __('Room')]" class="sm:w-36" :aria-label="__('View by')" />
        <x-ui.combobox wire:model.live="target" :options="$targets" class="sm:w-72" :aria-label="__('Choose')" />
        <x-ui.date-picker wire:model.live="date" class="sm:w-44" :aria-label="__('Day')" />
    </div>

    @if ($target === '')
        <x-ui.empty-state icon="calendar-days" :title="__('Choose a section, teacher or room')" />
    @else
        <x-ui.card class="mb-6" :title="$dayLabel" :padding="false">
            @if ($canAdjust && $by === 'section')
                <x-slot:actions><x-ui.button size="sm" variant="secondary" icon="plus" wire:click="openExtra">{{ __('Extra class') }}</x-ui.button></x-slot:actions>
            @endif
            <x-timetable.day :periods="$periods" :adjustable="$canAdjust" />
        </x-ui.card>

        <h2 class="mb-3 text-base font-semibold text-ink">{{ __('Week') }}@if ($week['term'] ?? null) <span class="font-normal text-ink-muted">· {{ $week['term']['name'] }}</span>@endif</h2>
        <x-timetable.week :entries="$week['entries'] ?? []" :show="$by" />
    @endif

    @if ($canAdjust)
        <x-ui.modal name="change-form" :title="$change['type'] === 'cancelled' ? __('Cancel this class for the day') : __('Assign a substitute')" size="sm">
            <form wire:submit="saveChange" id="change-form" class="space-y-4">
                @if ($change['type'] === 'substituted')
                    <x-ui.combobox :label="__('Substitute teacher')" wire:model="change.substitute_employee_id" :options="$teachers" required :hint="__('Must be free in that period.')" />
                @endif
                <x-ui.input :label="__('Reason')" wire:model="change.reason" optional />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'change-form')">{{ __('Close') }}</x-ui.button>
                <x-ui.button type="submit" form="change-form" loading="saveChange" :variant="$change['type'] === 'cancelled' ? 'danger' : 'primary'">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal name="extra-form" :title="__('Extra class on :date', ['date' => $dayLabel])" size="sm">
            <form wire:submit="saveExtra" id="extra-form" class="space-y-4">
                <x-ui.select :label="__('Period')" wire:model="extra.time_slot_id" :options="$extraSlots" :placeholder="__('Choose')" required />
                <x-ui.select :label="__('Course')" wire:model="extra.offering_section_id" :options="$extraClasses" :placeholder="__('Choose')" required />
                <x-ui.combobox :label="__('Teacher')" wire:model="extra.employee_id" :options="$teachers" optional />
                <x-ui.input :label="__('Reason')" wire:model="extra.reason" optional :hint="__('e.g. Make-up class')" />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'extra-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="extra-form" loading="saveExtra">{{ __('Add') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
