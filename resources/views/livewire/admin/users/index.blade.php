<div>
    <x-ui.page-header :title="__('Users')" :description="__('Everyone who can sign in to this institution.')">
        <x-slot:actions>
            @canapi('identity.users.import')
                <x-ui.button variant="secondary" icon="upload" :href="route('imports.index', ['type' => 'users'])">{{ __('Import') }}</x-ui.button>
            @endcanapi
            @canapi('identity.users.create')
                <x-ui.button icon="user-plus" wire:click="openCreate">{{ __('Add user') }}</x-ui.button>
            @endcanapi
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Name, email, mobile…')" :active="$chips">
        <x-ui.select wire:model.live="filters.role" :options="$roleNames" :placeholder="__('All roles')" class="sm:w-48" :aria-label="__('Role')" />
        <x-ui.select wire:model.live="filters.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('Any membership')" class="sm:w-44" :aria-label="__('Membership')" />
        <x-ui.select wire:model.live="filters.locked" :options="['1' => __('Locked'), '0' => __('Not locked')]" :placeholder="__('Any lock state')" class="sm:w-40" :aria-label="__('Locked')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,sort,gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th sortable="name" :sort="$sort">{{ __('Name') }}</x-ui.th>
            <x-ui.th>{{ __('Roles') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th sortable="last_login_at" :sort="$sort">{{ __('Last sign-in') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $user)
            <x-ui.tr wire:key="u-{{ $user['id'] }}" :href="route('users.show', $user['id'])">
                <x-ui.td primary>
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$user['name']" size="sm" />
                        <div class="min-w-0">
                            <a href="{{ route('users.show', $user['id']) }}" class="block truncate font-medium text-ink hover:underline">{{ $user['name'] }}</a>
                            <p class="truncate text-xs text-ink-muted">{{ $user['email'] }}</p>
                        </div>
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Roles')">
                    <div class="flex flex-wrap gap-1">
                        @forelse ($user['role_assignments'] ?? [] as $assignment)
                            <x-ui.badge size="sm">{{ $assignment['role']['display_name'] }}</x-ui.badge>
                        @empty
                            <span class="text-ink-subtle">—</span>
                        @endforelse
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Status')">
                    <div class="flex flex-wrap gap-1">
                        <x-ui.status-badge :status="$user['membership_status'] === 'inactive' ? 'inactive' : $user['status']" />
                        @if ($user['is_locked'])<x-ui.status-badge status="locked" />@endif
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Last sign-in')"><x-ui.time :value="$user['last_login_at']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState>
            <x-ui.empty-state compact icon="users" :title="__('No users found')" :description="__('Try another search or clear the filters.')" />
        </x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.drawer name="user-form" :title="__('Add user')" :description="__('If the email already has a CampusOS account, it is linked to this institution.')">
        <form wire:submit="create" id="user-form" class="space-y-5">
            <x-ui.input :label="__('Full name')" wire:model="form.name" required />
            <x-ui.input :label="__('Email')" type="email" wire:model="form.email" required />
            <x-ui.input :label="__('Mobile')" type="tel" wire:model="form.mobile" prefix="+91" optional />
            @canapi('identity.role_assignments.manage')
                <x-ui.combobox :label="__('Roles')" wire:model="form.roles" :options="$roleOptions" multiple optional />
            @endcanapi
            <x-ui.switch wire:model="form.send_invite" :label="__('Send invitation email')" :description="__('Off: a temporary password is shown once instead.')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'user-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="user-form" loading="create">{{ __('Create user') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.modal name="temporary-password" :title="__('Temporary password')" :description="__('Share it securely. It is shown only once; the user must change it at first sign-in.')" size="sm">
        @if ($temporaryPassword)<p class="text-center"><x-ui.code :value="$temporaryPassword" class="text-base" /></p>@endif
        <x-slot:footer><x-ui.button x-on:click="$dispatch('close-dialog', 'temporary-password')">{{ __('Done') }}</x-ui.button></x-slot:footer>
    </x-ui.modal>
</div>
