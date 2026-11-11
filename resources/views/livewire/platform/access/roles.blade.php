<div>
    <x-ui.page-header :title="__('Roles & permissions')" :description="__('Built-in roles used by every institution. Open a role to choose, module by module, what it can do.')" />

    <x-ui.table :empty="$roles === []">
        <x-slot:head>
            <x-ui.th>{{ __('Role') }}</x-ui.th>
            <x-ui.th>{{ __('People') }}</x-ui.th>
            <x-ui.th>{{ __('Permissions') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($roles as $r)
            <x-ui.tr wire:key="role-{{ $r['id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('platform.access.roles.show', $r['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $r['display_name'] }}</a>
                    @if ($r['is_customised'])<x-ui.badge size="sm" tone="info" class="ml-2">{{ __('Edited') }}</x-ui.badge>@endif
                    @if ($r['is_department_scoped'])<x-ui.badge size="sm" class="ml-1">{{ __('Per department') }}</x-ui.badge>@endif
                    <span class="block text-xs text-ink-muted">{{ $r['description'] }}</span>
                </x-ui.td>
                <x-ui.td :label="__('People')">{{ $r['users'] }}</x-ui.td>
                <x-ui.td :label="__('Permissions')">{{ $r['permissions_count'] }}</x-ui.td>
                <x-ui.td class="text-right"><x-ui.button size="sm" variant="ghost" icon-right="arrow-right" :href="route('platform.access.roles.show', $r['id'])">{{ __('Permissions') }}</x-ui.button></x-ui.td>
            </x-ui.tr>
        @endforeach
    </x-ui.table>
</div>
