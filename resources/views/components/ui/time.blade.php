{{--
    <x-ui.time :value="$iso" />            → "5 minutes ago" (exact date on hover)
    <x-ui.time :value="$iso" format="date" />  → "26 Oct 2026"
--}}
@props(['value', 'format' => 'relative'])
@php
    $iso = \App\Support\Format::iso($value);
@endphp
@if ($iso === null)
    <span {{ $attributes }}>—</span>
@elseif ($format === 'relative')
    <time datetime="{{ $iso }}" title="{{ \App\Support\Format::dateTime($value) }}" x-data="timeAgo(@js($iso))" x-text="label" {{ $attributes }}>{{ \App\Support\Format::dateTime($value) }}</time>
@else
    <time datetime="{{ $iso }}" {{ $attributes }}>{{ $format === 'date' ? \App\Support\Format::date($value) : \App\Support\Format::dateTime($value) }}</time>
@endif
