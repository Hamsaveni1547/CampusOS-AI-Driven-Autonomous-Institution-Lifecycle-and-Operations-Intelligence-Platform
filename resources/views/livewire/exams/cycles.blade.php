<div>
    <x-ui.page-header :title="__('Examinations')" :description="__('End-term exam cycles: registration, eligibility, timetable, marks and results.')">
        @canapi('exams.cycles.manage')
            <x-slot:actions><x-ui.button icon="plus" wire:click="create">{{ __('New exam cycle') }}</x-ui.button></x-slot:actions>
        @endcanapi
    </x-ui.page-header>

    <x-ui.table loading="gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Cycle') }}</x-ui.th>
            <x-ui.th>{{ __('Term') }}</x-ui.th>
            <x-ui.th>{{ __('Registrations') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $row)
            <x-ui.tr wire:key="cy-{{ $row['id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('exam-cycles.show', $row['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $row['name'] }}</a>
                    <span class="block text-xs text-ink-muted">{{ $types[$row['type']] ?? $row['type'] }}</span>
                </x-ui.td>
                <x-ui.td :label="__('Term')">{{ $row['term']['name'] }}</x-ui.td>
                <x-ui.td :label="__('Registrations')">{{ $row['registrations'] ?? 0 }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$row['status']" :label="$statuses[$row['status']] ?? null" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="file-check" :title="__('No exam cycles yet')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="cycle-form" :title="__('New exam cycle')" size="sm">
        <form wire:submit="save" id="cycle-form-el" class="space-y-4">
            <x-ui.input :label="__('Name')" wire:model="form.name" required :hint="__('e.g. November 2026 end-term')" />
            <x-ui.select :label="__('Type')" wire:model="form.type" :options="$types" required />
            <x-ui.select :label="__('Term')" wire:model="form.term_id" :options="$terms" required />
            <x-ui.input type="date" :label="__('Backlog registration closes on')" wire:model="form.registration_ends_on" optional />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'cycle-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="cycle-form-el" loading="save">{{ __('Create') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
