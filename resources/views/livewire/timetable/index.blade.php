<div>
    <x-ui.page-header :title="__('Timetables')" :description="__('Weekly timetable of each section for the term.')">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="calendar-days" :href="route('timetable-view.index')">{{ __('View by teacher or room') }}</x-ui.button>
            @canapi('timetable.manage')
                <x-ui.button variant="secondary" icon="clock" :href="route('periods.index')">{{ __('Periods') }}</x-ui.button>
            @endcanapi
            @if ($canManage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('New timetable') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4"><x-ui.select wire:model.live="term" :options="$terms" :placeholder="__('Current term')" class="sm:w-64" :aria-label="__('Term')" /></div>

    <x-ui.table loading="term" :empty="$timetables === []">
        <x-slot:head>
            <x-ui.th>{{ __('Section') }}</x-ui.th>
            <x-ui.th>{{ __('Periods') }}</x-ui.th>
            <x-ui.th>{{ __('Classes / week') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($timetables as $timetable)
            <x-ui.tr wire:key="tt-{{ $timetable['id'] }}" :href="route('timetables.show', $timetable['id'])">
                <x-ui.td primary><a href="{{ route('timetables.show', $timetable['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $timetable['section']['batch'] }} · {{ $timetable['section']['name'] }}</a></x-ui.td>
                <x-ui.td :label="__('Periods')">{{ $timetable['template']['name'] }}</x-ui.td>
                <x-ui.td :label="__('Classes / week')">{{ $timetable['periods_count'] ?? 0 }}</x-ui.td>
                <x-ui.td :label="__('Status')">
                    <x-ui.status-badge :status="$timetable['status']" />
                    @if ($timetable['published_at'])<span class="block text-xs text-ink-muted">v{{ $timetable['version'] }} · {{ \App\Support\Format::date($timetable['published_at']) }}</span>@endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="calendar-days" :title="__('No timetables for this term yet')" /></x-slot:emptyState>
    </x-ui.table>

    @if ($canManage)
        <x-ui.modal name="timetable-form" :title="__('New timetable')">
            <form wire:submit="create" id="timetable-form" class="space-y-4">
                <x-ui.combobox :label="__('Section')" wire:model="form.section_id" :options="$sections" required />
                <x-ui.select :label="__('Periods')" wire:model="form.slot_template_id" :options="$templates" :placeholder="__('Choose')" required />
                @if ($templates === [])<x-ui.alert tone="warning">{{ __('Create a period template first.') }}</x-ui.alert>@endif
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'timetable-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="timetable-form" loading="create">{{ __('Create') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
