@php($manage = $canManage)
<div>
    <x-ui.page-header :title="__('Departments')" :description="__('Academic and administrative departments of the institution.')">
        <x-slot:actions>
            @if ($manage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('Add department') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Name or code…')" :active="$chips">
        <x-ui.select wire:model.live="filters.type" :options="['academic' => __('Academic'), 'administrative' => __('Administrative')]" :placeholder="__('Any type')" class="sm:w-44" :aria-label="__('Type')" />
        <x-ui.select wire:model.live="filters.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters" :empty="$departments === []">
        <x-slot:head>
            <x-ui.th>{{ __('Department') }}</x-ui.th>
            <x-ui.th>{{ __('Type') }}</x-ui.th>
            <x-ui.th>{{ __('Programmes') }}</x-ui.th>
            <x-ui.th>{{ __('Staff') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($departments as $department)
            <x-ui.tr wire:key="dep-{{ $department['id'] }}">
                <x-ui.td primary>
                    <p class="font-medium text-ink">{{ $department['name'] }}</p>
                    <p class="font-mono text-xs text-ink-muted">{{ $department['code'] }}</p>
                </x-ui.td>
                <x-ui.td :label="__('Type')">{{ \App\Support\Format::label($department['type']) }}</x-ui.td>
                <x-ui.td :label="__('Programmes')"><a class="hover:underline" href="{{ route('programmes.index', ['f' => ['department' => $department['id']]]) }}">{{ $department['programmes_count'] ?? 0 }}</a></x-ui.td>
                <x-ui.td :label="__('Staff')"><a class="hover:underline" href="{{ route('staff.index', ['f' => ['department' => $department['id']]]) }}">{{ $department['active_staff_count'] ?? 0 }}</a></x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$department['status']" /></x-ui.td>
                <x-ui.td class="text-right">
                    @if ($manage)
                        <x-ui.dropdown align="right">
                            <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                            <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $department['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                            @if ($department['type'] === 'academic')
                                <x-ui.dropdown-item icon="user-round-cog" wire:click="openHod('{{ $department['id'] }}')">{{ __('Head of department') }}</x-ui.dropdown-item>
                            @endif
                            <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="askDelete('{{ $department['id'] }}')">{{ __('Delete') }}</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="building-2" :title="__('No departments yet')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.drawer name="record-form" :title="$editingId ? __('Edit department') : __('Add department')">
        <form wire:submit="save" id="record-form" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input :label="__('Code')" wire:model="form.code" required :hint="__('e.g. CSE')" />
                <x-ui.input :label="__('Name')" wire:model="form.name" required class="sm:col-span-2" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('Type')" wire:model="form.type" :options="['academic' => __('Academic'), 'administrative' => __('Administrative')]" />
                <x-ui.select :label="__('Status')" wire:model="form.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
                <x-ui.input :label="__('Email')" type="email" wire:model="form.email" optional />
                <x-ui.input :label="__('Phone')" wire:model="form.phone" optional />
                <x-ui.input :label="__('Established')" type="number" wire:model="form.established_year" optional />
            </div>
            <x-ui.textarea :label="__('Description')" wire:model="form.description" optional />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'record-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="record-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.modal name="hod-form" :title="__('Head of department')" :description="$hodFor['name'] ?? null">
        @if ($hodFor)
            @if ($hodFor['hod'])
                <p class="mb-4 text-sm text-ink-muted">{{ __('Current: :name, since :date', ['name' => $hodFor['hod']['name'], 'date' => \App\Support\Format::date($hodFor['hod']['since'])]) }}</p>
            @endif
            <form wire:submit="assignHod" id="hod-form">
                <x-ui.combobox :label="__('Teaching staff of this department')" wire:model="hodUserId" :options="$hodCandidates" required />
            </form>
            @if ($hodCandidates === [])
                <p class="mt-3 text-sm text-ink-muted">{{ __('Add active teaching staff to this department first.') }}</p>
            @endif
        @endif
        <x-slot:footer>
            @if ($hodFor['hod'] ?? null)
                <x-ui.button variant="ghost" class="mr-auto" wire:click="removeHod" loading="removeHod">{{ __('Remove') }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'hod-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="hod-form" loading="assignHod">{{ __('Assign') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-dialog name="delete-record" :title="__('Delete this department?')" :confirm-label="__('Delete')" action="delete">
        {{ __('Only departments without programmes or staff can be deleted. Mark it inactive instead to keep its history.') }}
    </x-ui.confirm-dialog>
</div>
