<div>
    <x-ui.page-header :title="$role['display_name']" :description="__('Tick what this role can do. Changes apply to every institution as soon as you save.')" :back="route('platform.access.roles')" :back-label="__('Roles & permissions')">
        <x-slot:meta>
            @if ($role['is_customised'])<x-ui.badge size="sm" tone="info">{{ __('Edited') }}</x-ui.badge>@endif
            <span class="font-mono text-xs text-ink-subtle">{{ $role['name'] }}</span>
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="rotate-ccw" wire:click="resetDefaults" wire:confirm="{{ __('Put this role back to its built-in name and permissions?') }}" loading="resetDefaults">{{ __('Reset to default') }}</x-ui.button>
            <x-ui.button icon="save" wire:click="save" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($formError)<x-ui.alert tone="danger" class="mb-4">{{ $formError }}</x-ui.alert>@endif

    <x-ui.card class="mb-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('Name shown to people')" wire:model="displayName" required />
            <x-ui.input :label="__('Description')" wire:model="description" optional />
        </div>
    </x-ui.card>

    <p class="mb-3 text-xs text-ink-muted">{{ __(':n permissions ticked.', ['n' => count($granted)]) }} <span class="text-info">●</span> {{ __('marks a difference from the built-in default.') }}</p>

    <div class="space-y-4">
        @foreach ($groups as $g)
            @php
                $names = array_column($g['permissions'], 'name');
                $on = count(array_intersect($names, $granted));
            @endphp
            <details wire:key="rg-{{ $g['module'] }}" class="rounded-lg border border-line bg-surface" @if ($on > 0) open @endif>
                <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-3">
                    <span class="font-medium text-ink">{{ $g['label'] }}</span>
                    <span class="flex items-center gap-3 text-xs text-ink-muted">
                        {{ __(':on of :all', ['on' => $on, 'all' => count($names)]) }}
                        <button type="button" wire:click.prevent="toggleModule('{{ $g['module'] }}', true)" class="text-primary-ink hover:underline">{{ __('All') }}</button>
                        <button type="button" wire:click.prevent="toggleModule('{{ $g['module'] }}', false)" class="text-ink-muted hover:underline">{{ __('None') }}</button>
                    </span>
                </summary>
                <div class="grid gap-2 border-t border-line px-5 py-3 sm:grid-cols-2">
                    @foreach ($g['permissions'] as $p)
                        @php($differs = in_array($p['name'], $granted, true) !== in_array($p['name'], $defaults, true))
                        <label wire:key="cb-{{ $p['name'] }}" class="flex items-start gap-2 text-sm">
                            <input type="checkbox" value="{{ $p['name'] }}" wire:model="granted" class="mt-1 rounded border-line" />
                            <span class="min-w-0">
                                <span class="text-ink">{{ $p['description'] }}</span>
                                @if ($differs)<span class="text-info" title="{{ __('Different from default') }}"> ●</span>@endif
                                <span class="block font-mono text-[11px] text-ink-subtle">{{ $p['name'] }}@if ($p['sensitive']) · {{ __('sensitive') }}@endif</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>
</div>
