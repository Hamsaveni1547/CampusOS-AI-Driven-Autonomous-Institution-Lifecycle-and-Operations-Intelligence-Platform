<div>
    <x-ui.page-header :title="__('Modules')" :description="__('What each module covers. Choose an institution to switch modules on or off for it; switched-off modules disappear from its menus and their pages are blocked.')" />

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-72"><x-ui.select :label="__('Institution')" wire:model.live="institution" :options="$institutions" :placeholder="__('Catalogue only')" /></div>
        @if ($institution !== '')
            <x-ui.button icon="save" wire:click="save" loading="save">{{ __('Save') }}</x-ui.button>
        @endif
    </div>

    <x-ui.table :empty="$modules === []">
        <x-slot:head>
            <x-ui.th>{{ __('Module') }}</x-ui.th>
            <x-ui.th>{{ __('Permissions') }}</x-ui.th>
            @if ($institution !== '')<x-ui.th>{{ __('Switched on') }}</x-ui.th>@endif
        </x-slot:head>
        @foreach ($modules as $m)
            <x-ui.tr wire:key="mod-{{ $m['key'] }}">
                <x-ui.td primary>
                    <span class="font-medium text-ink">{{ $m['label'] }}</span>
                    @if ($m['core'])<x-ui.badge size="sm" class="ml-2">{{ __('Core') }}</x-ui.badge>@endif
                    <span class="block text-xs text-ink-muted">{{ $m['description'] }}</span>
                </x-ui.td>
                <x-ui.td :label="__('Permissions')">{{ $m['permissions'] }}@if ($m['sensitive'])<span class="block text-xs text-warning">{{ __(':n sensitive', ['n' => $m['sensitive']]) }}</span>@endif</x-ui.td>
                @if ($institution !== '')
                    <x-ui.td :label="__('Switched on')">
                        @if ($m['core'])
                            <span class="text-xs text-ink-subtle">{{ __('Always on') }}</span>
                        @else
                            <x-ui.switch wire:model="enabled.{{ $m['key'] }}" :label="__('On')" />
                        @endif
                    </x-ui.td>
                @endif
            </x-ui.tr>
        @endforeach
    </x-ui.table>
</div>
