<div>
    <x-ui.page-header :title="__('Admission cycles')" :description="__('Each intake round with its dates, fees, required documents and seats.')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('New cycle') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters :active="$chips">
        <x-ui.select wire:model.live="filters.status" :options="['draft' => __('Draft'), 'open' => __('Open'), 'closed' => __('Closed')]" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="filters" :empty="$cycles === []">
        <x-slot:head>
            <x-ui.th>{{ __('Cycle') }}</x-ui.th>
            <x-ui.th>{{ __('Applications open') }}</x-ui.th>
            <x-ui.th>{{ __('Applications') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($cycles as $cycle)
            <x-ui.tr wire:key="cy-{{ $cycle['id'] }}" :href="route('admission-cycles.show', $cycle['id'])">
                <x-ui.td primary>
                    <a href="{{ route('admission-cycles.show', $cycle['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $cycle['name'] }}</a>
                    <p class="text-xs text-ink-muted">{{ $cycle['academic_year']['name'] ?? '' }}</p>
                </x-ui.td>
                <x-ui.td :label="__('Applications open')">{{ \App\Support\Format::date($cycle['opens_on']) }} – {{ \App\Support\Format::date($cycle['closes_on']) }}</x-ui.td>
                <x-ui.td :label="__('Applications')">{{ \App\Support\Format::number($cycle['applications_count'] ?? 0) }}</x-ui.td>
                <x-ui.td :label="__('Status')">
                    <x-ui.status-badge :status="$cycle['status']" />
                    @if ($cycle['is_accepting'])<x-ui.badge size="sm" tone="success" dot>{{ __('Accepting') }}</x-ui.badge>@endif
                </x-ui.td>
                <x-ui.td class="text-right">
                    <x-ui.dropdown>
                        <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $cycle['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="askDelete('{{ $cycle['id'] }}')">{{ __('Delete') }}</x-ui.dropdown-item>
                    </x-ui.dropdown>
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="calendar-range" :title="__('No admission cycles yet')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.drawer name="record-form" size="lg" :title="$editingId ? __('Edit admission cycle') : __('New admission cycle')">
        @if ($form !== [])
            <form wire:submit="save" id="record-form" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Name')" wire:model="form.name" required class="sm:col-span-2" :hint="__('e.g. B.E. admissions 2026')" />
                    <x-ui.select :label="__('Academic year')" wire:model="form.academic_year_id" :options="$years" :placeholder="__('Choose')" required />
                    <x-ui.input :label="__('Offer valid for (days)')" type="number" min="1" max="60" wire:model="form.offer_valid_days" />
                    <x-ui.date-picker :label="__('Applications open')" wire:model="form.opens_on" />
                    <x-ui.date-picker :label="__('Applications close')" wire:model="form.closes_on" />
                    <x-ui.input :label="__('Application fee')" type="number" min="0" step="0.01" wire:model="form.application_fee" prefix="₹" />
                </div>
                <x-ui.input :label="__('Required documents')" wire:model="form.required_documents" optional :hint="__('Comma separated, e.g. marks card, transfer certificate, category certificate')" />
                <x-ui.textarea :label="__('Instructions for applicants')" wire:model="form.instructions" rows="4" optional />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'record-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="record-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.confirm-dialog name="delete-record" :title="__('Delete this cycle?')" :confirm-label="__('Delete')" action="delete">
        {{ __('Only cycles without applications can be deleted. Close it instead to keep its history.') }}
    </x-ui.confirm-dialog>
</div>
