@php($staffTypes = \App\Livewire\Hr\Staff\Index::staffTypes())
@php($employmentTypes = \App\Livewire\Hr\Staff\Index::employmentTypes())
<div>
    <x-ui.page-header :title="__('Staff')" :description="__('Teaching and non-teaching staff. Each has a sign-in account.')">
        <x-slot:actions>
            @canapi('hr.employees.import')
                <x-ui.button variant="secondary" icon="upload" :href="route('imports.index', ['type' => 'employees'])">{{ __('Import') }}</x-ui.button>
            @endcanapi
            @if ($canManage)<x-ui.button icon="user-plus" wire:click="openCreate">{{ __('Add staff') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Name, email or code…')" :active="$chips">
        <x-ui.select wire:model.live="filters.department" :options="$departments" :placeholder="__('All departments')" class="sm:w-52" :aria-label="__('Department')" />
        <x-ui.select wire:model.live="filters.designation" :options="$designations" :placeholder="__('All designations')" class="sm:w-48" :aria-label="__('Designation')" />
        <x-ui.select wire:model.live="filters.staff_type" :options="$staffTypes" :placeholder="__('Any type')" class="sm:w-40" :aria-label="__('Type')" />
        <x-ui.select wire:model.live="filters.employment_type" :options="$employmentTypes" :placeholder="__('Any employment')" class="sm:w-40" :aria-label="__('Employment')" />
        <x-ui.select wire:model.live="filters.status" :options="\App\Livewire\Hr\Staff\Index::statuses()" :placeholder="__('Active')" class="sm:w-36" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,sort,gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th sortable="name" :sort="$sort">{{ __('Name') }}</x-ui.th>
            <x-ui.th sortable="code" :sort="$sort">{{ __('Code') }}</x-ui.th>
            <x-ui.th>{{ __('Department') }}</x-ui.th>
            <x-ui.th>{{ __('Designation') }}</x-ui.th>
            <x-ui.th sortable="joined_on" :sort="$sort">{{ __('Joined') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $employee)
            <x-ui.tr wire:key="e-{{ $employee['id'] }}" :href="route('staff.show', $employee['id'])">
                <x-ui.td primary>
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$employee['name']" size="sm" />
                        <div class="min-w-0">
                            <a href="{{ route('staff.show', $employee['id']) }}" wire:navigate class="block truncate font-medium text-ink hover:underline">{{ $employee['name'] }}</a>
                            <p class="truncate text-xs text-ink-muted">{{ $employee['email'] }}</p>
                        </div>
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Code')"><span class="font-mono text-xs">{{ $employee['employee_code'] }}</span></x-ui.td>
                <x-ui.td :label="__('Department')">{{ $employee['department']['name'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Designation')">{{ $employee['designation']['name'] ?? '—' }}<span class="block text-xs text-ink-muted">{{ $employmentTypes[$employee['employment_type']] ?? '' }}</span></x-ui.td>
                <x-ui.td :label="__('Joined')">{{ \App\Support\Format::date($employee['joined_on']) }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$employee['status']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="briefcase" :title="__('No staff found')" :description="__('Try another search or clear the filters.')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.drawer name="staff-form" size="lg" :title="__('Add staff')" :description="__('If the email already has a CampusOS account, it is linked.')">
        @if ($form !== [])
            <form wire:submit="create" id="staff-form" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Full name')" wire:model="form.name" required class="sm:col-span-2" />
                    <x-ui.input :label="__('Email')" type="email" wire:model="form.email" required />
                    <x-ui.input :label="__('Mobile')" type="tel" wire:model="form.mobile" optional />
                    <x-ui.select :label="__('Staff type')" wire:model.live="form.staff_type" :options="$staffTypes" required />
                    <x-ui.select :label="__('Employment')" wire:model.live="form.employment_type" :options="$employmentTypes" required />
                    <x-ui.combobox :label="__('Department')" wire:model="form.department_id" :options="$departments" optional />
                    <x-ui.combobox :label="__('Designation')" wire:model="form.designation_id" :options="$formDesignations" optional />
                    <x-ui.date-picker :label="__('Joined on')" wire:model="form.joined_on" />
                    @if (in_array($form['employment_type'], ['contract', 'visiting', 'guest', 'probation'], true))
                        <x-ui.date-picker :label="__('Contract ends')" wire:model="form.contract_ends_on" optional />
                    @endif
                    <x-ui.input :label="__('Employee code')" wire:model="form.employee_code" optional :hint="__('Generated when empty.')" />
                </div>
                <x-ui.switch wire:model="form.send_invite" :label="__('Send invitation email')" />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'staff-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="staff-form" loading="create">{{ __('Add staff') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
