{{--
    Searchable select, single or multiple. Keeps a hidden native <select> in sync so wire:model works.
    <x-ui.combobox label="Department" wire:model="form.department_id" :options="$departments" placeholder="Choose a department" />
    <x-ui.combobox label="Roles" wire:model="form.roles" :options="$roles" multiple />
    options: [value => label] or [['value' => .., 'label' => .., 'description' => ..]]
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'options' => [], 'placeholder' => null, 'multiple' => false, 'optional' => false, 'clearable' => true, 'value' => null])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
    $error ??= $key !== null ? $errors->first($key) : null;
    $placeholder ??= $multiple ? __('Select…') : __('Choose…');
    $normalised = collect($options)->map(fn ($option, $optionValue) => is_array($option)
        ? ['value' => (string) $option['value'], 'label' => (string) $option['label'], 'description' => $option['description'] ?? null]
        : ['value' => (string) $optionValue, 'label' => (string) $option, 'description' => null])->values()->all();
@endphp
<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$attributes->has('required')" :optional="$optional" class="{{ $attributes->get('class') }}">
    <div
        x-data="combobox({ options: @js($normalised), value: @js($value), multiple: @js((bool) $multiple), placeholder: @js($placeholder) })"
        x-on:keydown.escape="closeList()"
        x-on:click.outside="closeList()"
        class="relative"
    >
        <select x-ref="select" class="sr-only" tabindex="-1" aria-hidden="true" @if ($multiple) multiple @endif @if ($name) name="{{ $name }}{{ $multiple ? '[]' : '' }}" @endif {{ $attributes->except(['class', 'id', 'required']) }}>
            @unless ($multiple)<option value=""></option>@endunless
            @foreach ($normalised as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>

        <button type="button" id="{{ $id }}" x-ref="button" x-on:click="open ? closeList() : openList()"
            x-on:keydown.arrow-down.prevent="openList()"
            :aria-expanded="open" aria-haspopup="listbox" @if ($error) aria-invalid="true" @endif
            @class(['cos-control flex min-h-9 items-center gap-2 text-left', 'cos-control-invalid' => $error])>
            @if ($multiple)
                <span class="flex min-w-0 flex-1 flex-wrap gap-1">
                    <template x-for="value in selected" :key="value">
                        <span class="inline-flex items-center gap-1 rounded-sm bg-primary-soft px-1.5 py-0.5 text-xs font-medium text-primary-ink">
                            <span x-text="labelFor(value)"></span>
                            <span role="button" tabindex="-1" class="hover:text-ink" x-on:click.stop="remove(value)" aria-label="{{ __('Remove') }}">{!! \App\Support\Icons::svg('x', 'size-3') !!}</span>
                        </span>
                    </template>
                    <span x-show="selected.length === 0" class="text-ink-subtle">{{ $placeholder }}</span>
                </span>
            @else
                <span class="min-w-0 flex-1 truncate" :class="selected.length === 0 && 'text-ink-subtle'" x-text="summary"></span>
            @endif
            @if ($clearable)
                <span x-show="selected.length > 0" x-cloak role="button" tabindex="-1" x-on:click.stop="clear()" class="text-ink-subtle hover:text-ink" aria-label="{{ __('Clear') }}">{!! \App\Support\Icons::svg('x', 'size-4') !!}</span>
            @endif
            {!! \App\Support\Icons::svg('chevrons-up-down', 'size-4 text-ink-subtle') !!}
        </button>

        <div x-show="open" x-cloak x-transition.opacity.duration.100ms
            class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-line bg-surface-raised shadow-lg">
            <div class="border-b border-line p-2">
                <input type="text" x-ref="search" x-model="query" class="cos-control h-8 py-1"
                    placeholder="{{ __('Search…') }}"
                    x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter.prevent="choose(filtered[active])" aria-label="{{ __('Search options') }}">
            </div>
            <ul class="max-h-64 overflow-y-auto p-1" role="listbox" @if ($multiple) aria-multiselectable="true" @endif>
                <template x-for="(option, index) in filtered" :key="option.value">
                    <li role="option" :aria-selected="isSelected(option.value)"
                        x-on:click="choose(option)" x-on:mouseenter="active = index"
                        class="flex cursor-pointer items-start gap-2 rounded-md px-2.5 py-2 text-sm"
                        :class="active === index ? 'bg-surface-muted' : ''">
                        <span class="mt-0.5 w-4 text-primary">
                            <span x-show="isSelected(option.value)">{!! \App\Support\Icons::svg('check', 'size-4') !!}</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-ink" x-text="option.label"></span>
                            <span class="block truncate text-xs text-ink-muted" x-show="option.description" x-text="option.description"></span>
                        </span>
                    </li>
                </template>
                <li x-show="filtered.length === 0" class="px-3 py-6 text-center text-sm text-ink-muted">{{ __('No matches') }}</li>
            </ul>
        </div>
    </div>
</x-ui.field>
