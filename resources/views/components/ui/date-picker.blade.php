{{--
    Date input (native picker: accessible, works on every phone). Value format YYYY-MM-DD.
    <x-ui.date-picker label="Joined on" wire:model="form.joined_on" max="{{ now()->toDateString() }}" />
    type="datetime-local" or "month" also work.
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'type' => 'date', 'optional' => false])
<x-ui.input :label="$label" :hint="$hint" :error="$error" :name="$name" :type="$type" icon="calendar" :optional="$optional" {{ $attributes->class(['[&::-webkit-calendar-picker-indicator]:opacity-60']) }} />
