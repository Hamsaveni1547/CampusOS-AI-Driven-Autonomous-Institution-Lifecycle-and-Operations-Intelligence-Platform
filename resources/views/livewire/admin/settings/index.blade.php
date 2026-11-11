<div>
    <x-ui.page-header :title="$heading['title']" :description="$heading['description']" />

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
                                            @if (is_array($values[$setting['key']] ?? null))
                                                @php($rows = $values[$setting['key']])
                                                <div>
                                                    <p class="mb-1 text-sm font-medium text-ink">{{ $setting['label'] }}</p>
                                                    @if ($hint)<p class="mb-2 text-xs text-ink-muted">{{ $hint }}</p>@endif
                                                    <div class="overflow-x-auto rounded-lg border border-line">
                                                        <table class="w-full text-sm">
                                                            <thead class="bg-surface-muted text-left text-xs uppercase text-ink-subtle">
                                                                <tr>@foreach (array_keys($rows[0] ?? []) as $column)<th wire:key="th-{{ $setting['key'] }}-{{ $column }}" class="px-3 py-2">{{ \App\Support\Format::label($column) }}</th>@endforeach<th class="w-10"></th></tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($rows as $i => $row)
                                                                    <tr wire:key="row-{{ $setting['key'] }}-{{ $i }}" class="border-t border-line">
                                                                        @foreach (array_keys($row) as $column)
                                                                            <td wire:key="cell-{{ $setting['key'] }}-{{ $i }}-{{ $column }}" class="px-2 py-1.5"><input type="text" wire:model="values.{{ $setting['key'] }}.{{ $i }}.{{ $column }}" class="w-full rounded-md border border-line bg-surface px-2 py-1 text-sm" /></td>
                                                                        @endforeach
                                                                        <td class="px-2"><button type="button" wire:click="removeRow('{{ $setting['key'] }}', {{ $i }})" class="text-ink-subtle hover:text-danger" aria-label="{{ __('Remove row') }}"><x-ui.icon name="x" class="size-4" /></button></td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <x-ui.button class="mt-2" size="sm" variant="ghost" icon="plus" wire:click="addRow('{{ $setting['key'] }}')">{{ __('Add row') }}</x-ui.button>
                                                    @error('values.'.$setting['key'])<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                                                </div>
                                            @else
                                                <x-ui.input :label="$setting['label']" wire:model="values.{{ $setting['key'] }}" :hint="__('Separate values with commas.').($hint ? ' '.$hint : '')" />
                                            @endif
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
