<div>
    <x-ui.page-header :title="__('Settings')" :description="__('Values left at their default follow the platform default.')" />

    <div class="grid gap-6 lg:grid-cols-4">
        <nav class="flex gap-1 overflow-x-auto lg:flex-col" aria-label="{{ __('Setting groups') }}">
            @foreach ($groups as $item)
                <button type="button" wire:key="g-{{ $item['group'] }}" wire:click="$set('group', '{{ $item['group'] }}')"
                    @class([
                        'shrink-0 rounded-md px-3 py-2 text-left text-sm font-medium transition',
                        'bg-primary-soft text-primary-ink' => $item['group'] === $group,
                        'text-ink-muted hover:bg-surface-muted hover:text-ink' => $item['group'] !== $group,
                    ])>{{ $item['label'] }}</button>
            @endforeach
        </nav>

        <div class="lg:col-span-3">
            @if ($current)
                <form wire:submit="save">
                    <x-ui.card :title="$current['label']">
                        <fieldset @disabled(! $canManage) class="space-y-5">
                            @foreach ($current['settings'] as $setting)
                                @php($hint = $setting['secret'] ? __('Stored securely. Leave empty to keep the current value.') : ($setting['default'] !== null && $setting['default'] !== '' ? __('Default: :value', ['value' => is_bool($setting['default']) ? ($setting['default'] ? __('On') : __('Off')) : (is_array($setting['default']) ? implode(', ', $setting['default']) : $setting['default'])]) : null))
                                <div wire:key="s-{{ $group }}-{{ $setting['key'] }}">
                                    @switch($setting['type'])
                                        @case('bool')
                                            <x-ui.switch wire:model="values.{{ $setting['key'] }}" :label="$setting['label']" :description="$hint" />
                                            @break
                                        @case('int')
                                            <x-ui.input type="number" :label="$setting['label']" wire:model="values.{{ $setting['key'] }}" :hint="$hint" />
                                            @break
                                        @case('array')
                                            <x-ui.input :label="$setting['label']" wire:model="values.{{ $setting['key'] }}" :hint="__('Separate values with commas.').($hint ? ' '.$hint : '')" />
                                            @break
                                        @default
                                            <x-ui.input :type="$setting['secret'] ? 'password' : 'text'" :label="$setting['label']" wire:model="values.{{ $setting['key'] }}" :hint="$hint" :placeholder="$setting['secret'] && $setting['value'] ? '••••••••' : null" />
                                    @endswitch
                                </div>
                            @endforeach
                        </fieldset>
                    </x-ui.card>
                    @if ($canManage)
                        <div class="mt-4 flex justify-end"><x-ui.button type="submit" loading="save">{{ __('Save settings') }}</x-ui.button></div>
                    @endif
                </form>
            @else
                <x-ui.empty-state icon="settings" :title="__('No settings available')" />
            @endif
        </div>
    </div>
</div>
