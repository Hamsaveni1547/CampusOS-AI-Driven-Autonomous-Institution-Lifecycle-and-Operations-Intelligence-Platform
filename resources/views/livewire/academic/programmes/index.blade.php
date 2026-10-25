<div>
    <x-ui.page-header :title="__('Programmes')" :description="__('Degrees and courses offered, such as B.E. Computer Science.')">
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('Add programme') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Name or code…')" :active="$chips">
        <x-ui.select wire:model.live="filters.department" :options="$departments" :placeholder="__('All departments')" class="sm:w-56" :aria-label="__('Department')" />
        <x-ui.select wire:model.live="filters.level" :options="$levels" :placeholder="__('Any level')" class="sm:w-44" :aria-label="__('Level')" />
        <x-ui.select wire:model.live="filters.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters" :empty="$programmes === []">
        <x-slot:head>
            <x-ui.th>{{ __('Programme') }}</x-ui.th>
            <x-ui.th>{{ __('Department') }}</x-ui.th>
            <x-ui.th>{{ __('Level') }}</x-ui.th>
            <x-ui.th>{{ __('Duration') }}</x-ui.th>
            <x-ui.th>{{ __('Intake') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($programmes as $programme)
            <x-ui.tr wire:key="pr-{{ $programme['id'] }}">
                <x-ui.td primary>
                    <p class="font-medium text-ink">{{ $programme['name'] }}</p>
                    <p class="font-mono text-xs text-ink-muted">{{ $programme['code'] }}@if ($programme['regulation']) · {{ $programme['regulation'] }}@endif</p>
                </x-ui.td>
                <x-ui.td :label="__('Department')">{{ $programme['department']['name'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Level')">{{ $levels[$programme['level']] ?? $programme['level'] }}</x-ui.td>
                <x-ui.td :label="__('Duration')">{{ trans_choice(':count year|:count years', $programme['duration_years']) }} · {{ trans_choice(':count term|:count terms', $programme['total_terms']) }}</x-ui.td>
                <x-ui.td :label="__('Intake')">{{ $programme['intake_capacity'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$programme['status']" /></x-ui.td>
                <x-ui.td class="text-right">
                    @if ($canManage)
                        <x-ui.dropdown>
                            <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                            <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $programme['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="layers" :href="route('batches.index', ['f' => ['programme' => $programme['id']]])">{{ __('Batches') }}</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="askDelete('{{ $programme['id'] }}')">{{ __('Delete') }}</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="graduation-cap" :title="__('No programmes found')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.drawer name="record-form" :title="$editingId ? __('Edit programme') : __('Add programme')">
        <form wire:submit="save" id="record-form" class="space-y-5">
            <x-ui.combobox :label="__('Department')" wire:model="form.department_id" :options="$departments" required />
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input :label="__('Code')" wire:model="form.code" required :hint="__('e.g. BE-CSE')" />
                <x-ui.input :label="__('Name')" wire:model="form.name" required class="sm:col-span-2" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('Level')" wire:model="form.level" :options="$levels" required />
                <x-ui.select :label="__('Status')" wire:model="form.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
                <x-ui.input :label="__('Duration (years)')" type="number" min="1" max="7" wire:model="form.duration_years" required />
                <x-ui.input :label="__('Terms')" type="number" min="1" max="21" wire:model="form.total_terms" optional :hint="__('Default: two per year.')" />
                <x-ui.input :label="__('Intake per year')" type="number" wire:model="form.intake_capacity" optional />
                <x-ui.input :label="__('Regulation')" wire:model="form.regulation" optional :hint="__('e.g. 2022 Scheme')" />
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'record-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="record-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.confirm-dialog name="delete-record" :title="__('Delete this programme?')" :confirm-label="__('Delete')" action="delete">
        {{ __('Only programmes without batches can be deleted.') }}
    </x-ui.confirm-dialog>
</div>
