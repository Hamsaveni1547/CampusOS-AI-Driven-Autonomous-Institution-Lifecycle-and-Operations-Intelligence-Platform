<div>
    <x-ui.page-header :title="__('Roles & permissions')" :description="__('Built-in roles are fixed. Create custom roles for anything else.')">
        <x-slot:actions>
            @canapi('identity.roles.manage')
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('New role') }}</x-ui.button>
            @endcanapi
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table :empty="$roles === []">
        <x-slot:head>
            <x-ui.th>{{ __('Role') }}</x-ui.th>
            <x-ui.th>{{ __('Type') }}</x-ui.th>
            <x-ui.th>{{ __('Permissions') }}</x-ui.th>
            <x-ui.th>{{ __('Holders') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($roles as $role)
            <x-ui.tr wire:key="role-{{ $role['id'] }}">
                <x-ui.td primary>
                    <button type="button" wire:click="open('{{ $role['id'] }}')" class="text-left font-medium text-ink hover:underline">{{ $role['display_name'] }}</button>
                    @if ($role['description'])<p class="text-xs text-ink-muted">{{ $role['description'] }}</p>@endif
                </x-ui.td>
                <x-ui.td :label="__('Type')">
                    <div class="flex flex-wrap gap-1">
                        <x-ui.badge size="sm" :tone="$role['is_system'] ? 'neutral' : 'primary'">{{ $role['is_system'] ? __('Built-in') : __('Custom') }}</x-ui.badge>
                        @if ($role['is_department_scoped'])<x-ui.badge size="sm" tone="info">{{ __('Department') }}</x-ui.badge>@endif
                        @unless ($role['is_active'])<x-ui.status-badge status="inactive" size="sm" />@endunless
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Permissions')">{{ count($role['permissions'] ?? []) }}</x-ui.td>
                <x-ui.td :label="__('Holders')">{{ $role['active_assignments_count'] ?? 0 }}</x-ui.td>
                <x-ui.td class="text-right">
                    @if (! $role['is_system'] && ($role['active_assignments_count'] ?? 0) === 0)
                        @canapi('identity.roles.manage')
                            <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="askDelete('{{ $role['id'] }}')" :aria-label="__('Delete')" />
                        @endcanapi
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="shield" :title="__('No roles')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.drawer name="role-form" size="lg" :title="$editingId ? $form['display_name'] : __('New role')" :description="$readOnly ? __('Built-in role: read only.') : null">
        <form wire:submit="save" id="role-form" class="space-y-5">
            <fieldset @disabled($readOnly) class="space-y-5">
                <x-ui.input :label="__('Name')" wire:model="form.display_name" required />
                <x-ui.textarea :label="__('Description')" wire:model="form.description" rows="2" optional />
                <x-ui.switch wire:model="form.is_department_scoped" :label="__('Department role')" :description="__('Assigned per department, like Head of Department.')" />
                @if ($editingId)
                    <x-ui.switch wire:model="form.is_active" :label="__('Active')" />
                @endif
            </fieldset>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-ink">{{ __('Permissions') }} <span class="font-normal text-ink-muted">({{ count($form['permissions']) }})</span></h3>
                <div class="divide-y divide-line rounded-lg border border-line">
                    @foreach ($modules as $module)
                        @php($names = collect($module['permissions'])->pluck('name')->all())
                        @php($ticked = count(array_intersect($names, $form['permissions'])))
                        <details wire:key="mod-{{ $module['module'] }}" class="group" @if ($ticked > 0) open @endif>
                            <summary class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-ink">
                                <span>{{ \App\Support\Format::label($module['module']) }} <span class="text-ink-muted">{{ $ticked }}/{{ count($names) }}</span></span>
                                @unless ($readOnly)
                                    <span class="flex gap-2 text-xs">
                                        <button type="button" class="text-primary-ink hover:underline" wire:click.stop="toggleModule(@js($names), true)">{{ __('All') }}</button>
                                        <button type="button" class="text-ink-muted hover:underline" wire:click.stop="toggleModule(@js($names), false)">{{ __('None') }}</button>
                                    </span>
                                @endunless
                            </summary>
                            <div class="grid gap-3 px-4 pb-4 sm:grid-cols-2">
                                @foreach ($module['permissions'] as $permission)
                                    <label wire:key="p-{{ $permission['name'] }}" class="flex items-start gap-2 text-sm">
                                        <input type="checkbox" value="{{ $permission['name'] }}" wire:model="form.permissions" @disabled($readOnly)
                                            class="mt-0.5 size-4 rounded border-line-strong text-primary focus:ring-primary">
                                        <span>
                                            <span class="block text-ink">{{ $permission['description'] ?: $permission['name'] }}</span>
                                            <span class="block font-mono text-xs text-ink-subtle">{{ $permission['name'] }}@if ($permission['is_sensitive']) · <span class="text-warning">{{ __('sensitive') }}</span>@endif</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
                @error('form.permissions')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'role-form')">{{ $readOnly ? __('Close') : __('Cancel') }}</x-ui.button>
            @unless ($readOnly)
                <x-ui.button type="submit" form="role-form" loading="save">{{ __('Save') }}</x-ui.button>
            @endunless
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.confirm-dialog name="delete-role" :title="__('Delete this role?')" :confirm-label="__('Delete')" action="delete">
        {{ __('Only roles that nobody holds can be deleted.') }}
    </x-ui.confirm-dialog>
</div>
