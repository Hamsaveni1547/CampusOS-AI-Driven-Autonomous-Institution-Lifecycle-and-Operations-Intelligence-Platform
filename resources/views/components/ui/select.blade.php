{{--
    Native select (best for short lists and mobile).
    <x-ui.select label="Level" wire:model="level" :options="['ug' => 'Undergraduate', 'pg' => 'Postgraduate']" placeholder="Choose…" />
    options: [value => label] or [['value' => .., 'label' => ..]]; or pass <option> elements in the slot.
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'options' => [], 'placeholder' => null, 'optional' => false])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
    $error ??= $key !== null ? $errors->first($key) : null;
    $normalised = collect($options)->map(fn ($option, $value) => is_array($option)
        ? ['value' => (string) $option['value'], 'label' => (string) $option['label']]
        : ['value' => (string) $value, 'label' => (string) $option])->values();
@endphp
<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$attributes->has('required')" :optional="$optional" class="{{ $attributes->get('class') }}">
    <div class="relative">
        <select id="{{ $id }}" @if ($name) name="{{ $name }}" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->class(['cos-control appearance-none pr-9', 'cos-control-invalid' => $error]) }}>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($normalised as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-ink-subtle">
            <x-ui.icon name="chevron-down" class="size-4" />
        </span>
    </div>
</x-ui.field>
