@php($active = $user['membership_status'] !== 'inactive' && $user['status'] !== 'inactive')
<div>
    <x-ui.page-header :title="$user['name']" :description="$user['email']" :back="route('users.index')" :back-label="__('Users')">
        <x-slot:meta>
            <x-ui.status-badge :status="$active ? $user['status'] : 'inactive'" />
            @if ($user['is_locked'])<x-ui.status-badge status="locked" />@endif
        </x-slot:meta>
        <x-slot:actions>
            @canapi('identity.users.update')
                <x-ui.button variant="secondary" icon="pencil" wire:click="edit">{{ __('Edit') }}</x-ui.button>
            @endcanapi
            <x-ui.dropdown>
                <x-slot:trigger><x-ui.button variant="secondary" icon-right="chevron-down">{{ __('Actions') }}</x-ui.button></x-slot:trigger>
                @if ($user['status'] === 'invited')
                    @canapi('identity.users.create')<x-ui.dropdown-item icon="send" wire:click="resendInvitation">{{ __('Resend invitation') }}</x-ui.dropdown-item>@endcanapi
                @endif
                @if ($user['is_locked'])
                    @canapi('identity.users.unlock')<x-ui.dropdown-item icon="lock-open" wire:click="unlock">{{ __('Unlock account') }}</x-ui.dropdown-item>@endcanapi
                @endif
                @canapi('identity.users.reset_password')
                    <x-ui.dropdown-item icon="key-round" x-on:click="$dispatch('open-dialog', 'reset-password')">{{ __('Reset password') }}</x-ui.dropdown-item>
                    @if ($user['mfa_enabled'] ?? false)
                        <x-ui.dropdown-item icon="shield-alert" x-on:click="$dispatch('open-dialog', 'reset-mfa')">{{ __('Reset two-step verification') }}</x-ui.dropdown-item>
                    @endif
                @endcanapi
                @canapi('identity.users.deactivate')
                    @if ($active)
                        <x-ui.dropdown-item icon="user-x" tone="danger" x-on:click="$dispatch('open-dialog', 'deactivate-user')">{{ __('Deactivate') }}</x-ui.dropdown-item>
                    @else
                        <x-ui.dropdown-item icon="user-check" wire:click="activate">{{ __('Re-activate') }}</x-ui.dropdown-item>
                    @endif
                @endcanapi
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card :title="__('Details')">
                <x-ui.description-list :items="[
                    __('Email') => $user['email'].($user['email_verified'] ? '' : ' ('.__('not verified').')'),
                    __('Mobile') => $user['mobile'],
                    __('Username') => $user['username'],
                    __('Two-step verification') => ($user['mfa_enabled'] ?? false) ? __('On') : __('Off'),
                    __('Last sign-in') => \App\Support\Format::dateTime($user['last_login_at']),
                    __('Created') => \App\Support\Format::date($user['created_at']),
                ]" />
            </x-ui.card>

            <x-ui.card :title="__('Roles')" :padding="false">
                @canapi('identity.role_assignments.manage')
                    <x-slot:actions><x-ui.button size="sm" icon="plus" x-on:click="$dispatch('open-dialog', 'assign-role')">{{ __('Assign role') }}</x-ui.button></x-slot:actions>
                @endcanapi
                @forelse ($user['role_assignments'] ?? [] as $assignment)
                    <div wire:key="ra-{{ $assignment['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ $assignment['role']['display_name'] }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ $assignment['department']['name'] ?? __('Whole institution') }}
                                @if ($assignment['valid_until']) · {{ __('until :date', ['date' => \App\Support\Format::date($assignment['valid_until'])]) }} @endif
                            </p>
                        </div>
                        <x-ui.status-badge :status="$assignment['status']" size="sm" />
                        @if ($assignment['status'] === 'active')
                            @canapi('identity.role_assignments.manage')
                                <x-ui.button size="sm" variant="ghost" wire:click="askRevoke('{{ $assignment['id'] }}')">{{ __('Revoke') }}</x-ui.button>
                            @endcanapi
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state compact icon="shield" :title="__('No roles yet')" />
                @endforelse
            </x-ui.card>
        </div>

        @canapi('identity.security_events.view')
            <x-ui.card :title="__('Recent security activity')">
                @if ($events === [])
                    <p class="text-sm text-ink-muted">{{ __('No activity yet.') }}</p>
                @else
                    <x-ui.timeline :items="collect($events)->map(fn ($e) => [
                        'title' => \App\Support\Format::label($e['event']),
                        'description' => trim(($e['ip_address'] ?? '').' '.(isset($e['actor']['name']) ? '· '.__('by :name', ['name' => $e['actor']['name']]) : '')),
                        'time' => $e['created_at'],
                        'icon' => str_starts_with($e['event'], 'login') ? 'log-in' : 'shield',
                        'tone' => $e['severity'] === 'critical' ? 'danger' : ($e['severity'] === 'warning' ? 'warning' : 'neutral'),
                    ])->all()" />
                @endif
            </x-ui.card>
        @endcanapi
    </div>

    <x-ui.drawer name="edit-user" :title="__('Edit user')">
        <form wire:submit="save" id="edit-user-form" class="space-y-5">
            <x-ui.input :label="__('Full name')" wire:model="form.name" required />
            <x-ui.input :label="__('Email')" type="email" wire:model="form.email" :hint="__('Changing it asks the user to verify the new address.')" required />
            <x-ui.input :label="__('Mobile')" wire:model="form.mobile" optional />
            <x-ui.input :label="__('Username')" wire:model="form.username" optional />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'edit-user')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="edit-user-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.modal name="assign-role" :title="__('Assign role')">
        <form wire:submit="assignRole" id="assign-role-form" class="space-y-4">
            <x-ui.combobox :label="__('Role')" wire:model="assign.role_id" :options="$roleOptions" required />
            <x-ui.combobox :label="__('Department')" wire:model="assign.department_id" :options="$departments" optional :hint="__('Only for department roles such as Head of Department.')" />
            <x-ui.date-picker :label="__('Valid until')" wire:model="assign.valid_until" optional :hint="__('Leave empty for no end date.')" />
            <x-ui.input :label="__('Reason')" wire:model="assign.reason" optional />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'assign-role')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="assign-role-form" loading="assignRole">{{ __('Assign') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-dialog name="revoke-role" :title="__('Revoke this role?')" :confirm-label="__('Revoke')" action="revoke">
        {{ __('The user loses its permissions immediately.') }}
        <x-slot:fields><div class="mt-4"><x-ui.input :label="__('Reason')" wire:model="reason" optional /></div></x-slot:fields>
    </x-ui.confirm-dialog>

    <x-ui.confirm-dialog name="deactivate-user" :title="__('Deactivate :name?', ['name' => $user['name']])" :confirm-label="__('Deactivate')" action="deactivate" :confirm-text="$user['name']">
        {{ __('They are signed out and cannot sign in to this institution. Their records stay.') }}
        <x-slot:fields><div class="mt-4"><x-ui.input :label="__('Reason')" wire:model="reason" required /></div></x-slot:fields>
    </x-ui.confirm-dialog>

    <x-ui.modal name="reset-password" :title="__('Reset password')" size="sm">
        <p class="text-sm text-ink-muted">{{ __('Email a reset link, or set a temporary password that must be changed at next sign-in.') }}</p>
        <x-slot:footer>
            <x-ui.button variant="secondary" wire:click="resetPassword('temporary_password')" loading="resetPassword">{{ __('Temporary password') }}</x-ui.button>
            <x-ui.button wire:click="resetPassword('link')" loading="resetPassword">{{ __('Email reset link') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-dialog name="reset-mfa" :title="__('Reset two-step verification?')" :confirm-label="__('Reset')" action="resetMfa">
        {{ __('Use this when the user lost their phone. They are signed out and set it up again at next sign-in.') }}
    </x-ui.confirm-dialog>

    <x-ui.modal name="temporary-password" :title="__('Temporary password')" size="sm">
        @if ($temporaryPassword)<p class="text-center"><x-ui.code :value="$temporaryPassword" class="text-base" /></p>@endif
        <x-slot:footer><x-ui.button x-on:click="$dispatch('close-dialog', 'temporary-password')">{{ __('Done') }}</x-ui.button></x-slot:footer>
    </x-ui.modal>
</div>
