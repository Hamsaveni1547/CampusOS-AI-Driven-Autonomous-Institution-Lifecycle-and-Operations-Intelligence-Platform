<div>
    <x-ui.page-header :title="__('Leave setup')" :description="__('Leave types, and giving every staff member their days for the year.')">
        <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Leave type') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="mb-6" :title="__('Yearly allotment')">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-32"><x-ui.input type="number" :label="__('Year')" wire:model="year" /></div>
            <x-ui.button icon="calendar-plus" wire:click="allot" loading="allot">{{ __('Allot balances') }}</x-ui.button>
            <p class="text-sm text-ink-muted">{{ __('Gives every working staff member the yearly days of each paid type. Existing balances are kept.') }}</p>
        </div>
    </x-ui.card>

    <x-ui.table :empty="$types === []">
        <x-slot:head>
            <x-ui.th>{{ __('Leave type') }}</x-ui.th>
            <x-ui.th>{{ __('Days per year') }}</x-ui.th>
            <x-ui.th>{{ __('Rules') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($types as $t)
            <x-ui.tr wire:key="lt-{{ $t['id'] }}">
                <x-ui.td primary><button type="button" wire:click="open('{{ $t['id'] }}')" class="font-medium text-ink hover:underline">{{ $t['name'] }}</button><span class="block font-mono text-xs text-ink-subtle">{{ $t['code'] }}</span></x-ui.td>
                <x-ui.td :label="__('Days per year')">{{ $t['is_paid'] ? $t['days_per_year'] + 0 : '—' }}</x-ui.td>
                <x-ui.td :label="__('Rules')">{{ $t['is_paid'] ? __('Paid') : __('Unpaid (loss of pay)') }}@if ($t['allow_half_day']) · {{ __('half day allowed') }}@endif</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$t['status']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="calendar-x" :title="__('No leave types yet')" :description="__('e.g. Casual leave 12, Earned leave 15, Medical leave 10, Leave without pay.')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.modal name="leave-type" :title="($type['id'] ?? '') ? __('Edit leave type') : __('New leave type')" size="sm">
        <form wire:submit="save" id="leave-type-form" class="space-y-4">
            <x-ui.input :label="__('Code')" wire:model="type.code" required placeholder="CL" />
            <x-ui.input :label="__('Name')" wire:model="type.name" required />
            <x-ui.switch wire:model.live="type.is_paid" :label="__('Paid leave')" :description="__('Unpaid leave becomes loss of pay in payroll.')" />
            @if ($type['is_paid'] ?? true)
                <x-ui.input type="number" step="0.5" min="0" :label="__('Days per year')" wire:model="type.days_per_year" required />
            @endif
            <x-ui.switch wire:model="type.allow_half_day" :label="__('Allow half day')" />
            <x-ui.select :label="__('Status')" wire:model="type.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'leave-type')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="leave-type-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
