<div>
    <x-ui.page-header :title="__('Institutions')" :description="__('Every institution on this CampusOS platform.')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('New institution') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Name or code…')" :active="$chips">
        <x-ui.select wire:model.live="filters.type" :options="$types" :placeholder="__('Any type')" class="sm:w-48" :aria-label="__('Type')" />
        <x-ui.select wire:model.live="filters.status" :options="['active' => __('Active'), 'suspended' => __('Suspended')]" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,sort,gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th sortable="name" :sort="$sort">{{ __('Institution') }}</x-ui.th>
            <x-ui.th>{{ __('Type') }}</x-ui.th>
            <x-ui.th>{{ __('Part of') }}</x-ui.th>
            <x-ui.th>{{ __('Users') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th sortable="created_at" :sort="$sort">{{ __('Created') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $institution)
            <x-ui.tr wire:key="inst-{{ $institution['id'] }}">
                <x-ui.td primary>
                    <p class="font-medium text-ink">{{ $institution['name'] }}</p>
                    <p class="font-mono text-xs text-ink-muted">{{ $institution['code'] }}@if ($institution['address']['city']) · {{ $institution['address']['city'] }}@endif</p>
                </x-ui.td>
                <x-ui.td :label="__('Type')">{{ $types[$institution['type']] ?? $institution['type'] }}</x-ui.td>
                <x-ui.td :label="__('Part of')">{{ $institution['parent']['name'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Users')">{{ \App\Support\Format::number($institution['users_count'] ?? 0) }}</x-ui.td>
                <x-ui.td :label="__('Status')">
                    <x-ui.status-badge :status="$institution['status']" />
                    @if ($institution['suspension_reason'] ?? null)<p class="mt-1 text-xs text-ink-muted">{{ $institution['suspension_reason'] }}</p>@endif
                </x-ui.td>
                <x-ui.td :label="__('Created')">{{ \App\Support\Format::date($institution['created_at']) }}</x-ui.td>
                <x-ui.td class="text-right">
                    <x-ui.dropdown>
                        <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $institution['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                        @if ($institution['status'] === 'suspended')
                            <x-ui.dropdown-item icon="circle-check" wire:click="reactivate('{{ $institution['id'] }}')">{{ __('Re-activate') }}</x-ui.dropdown-item>
                        @else
                            <x-ui.dropdown-item icon="ban" tone="danger" wire:click="askSuspend('{{ $institution['id'] }}')">{{ __('Suspend') }}</x-ui.dropdown-item>
                        @endif
                    </x-ui.dropdown>
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="building" :title="__('No institutions found')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.drawer name="institution-form" size="lg" :title="$editingId ? __('Edit institution') : __('New institution')">
        @if ($form !== [])
            <form wire:submit="save" id="institution-form" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.input :label="__('Code')" wire:model="form.code" required :disabled="$editingId !== null" :hint="__('Used in sign-up links. Cannot change later.')" />
                    <x-ui.input :label="__('Name')" wire:model="form.name" required class="sm:col-span-2" />
                    <x-ui.input :label="__('Short name')" wire:model="form.short_name" optional />
                    <x-ui.select :label="__('Type')" wire:model="form.type" :options="$types" :disabled="$editingId !== null" />
                    @unless ($editingId)
                        <x-ui.combobox :label="__('Part of university')" wire:model="form.parent_id" :options="$universities" optional />
                    @endunless
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Email')" type="email" wire:model="form.email" optional />
                    <x-ui.input :label="__('Phone')" wire:model="form.phone" optional />
                    <x-ui.input :label="__('Website')" type="url" wire:model="form.website" optional />
                    <x-ui.input :label="__('Affiliation')" wire:model="form.affiliation" optional />
                    <x-ui.input :label="__('City')" wire:model="form.city" optional />
                    <x-ui.input :label="__('State')" wire:model="form.state" optional />
                    <x-ui.input :label="__('Country code')" wire:model="form.country" maxlength="2" required />
                    <x-ui.input :label="__('Currency')" wire:model="form.currency" maxlength="3" required />
                    <x-ui.combobox :label="__('Time zone')" wire:model="form.timezone" :options="collect(timezone_identifiers_list())->mapWithKeys(fn ($tz) => [$tz => $tz])->all()" required class="sm:col-span-2" />
                </div>
                @unless ($editingId)
                    <x-ui.divider />
                    <div>
                        <h3 class="text-sm font-semibold text-ink">{{ __('First administrator') }}</h3>
                        <p class="mb-3 text-sm text-ink-muted">{{ __('They get an email to set their password and finish the setup.') }}</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input :label="__('Full name')" wire:model="admin.name" required class="sm:col-span-2" />
                            <x-ui.input :label="__('Email')" type="email" wire:model="admin.email" required />
                            <x-ui.input :label="__('Mobile')" type="tel" wire:model="admin.mobile" optional />
                        </div>
                    </div>
                @endunless
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'institution-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="institution-form" loading="save">{{ $editingId ? __('Save') : __('Create institution') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.confirm-dialog name="suspend-institution" :title="__('Suspend this institution?')" :confirm-label="__('Suspend')" action="suspend">
        {{ __('Everyone in it is signed out and cannot sign in until it is re-activated. No data is deleted.') }}
        <x-slot:fields><div class="mt-4"><x-ui.input :label="__('Reason')" wire:model="reason" required /></div></x-slot:fields>
    </x-ui.confirm-dialog>
</div>
