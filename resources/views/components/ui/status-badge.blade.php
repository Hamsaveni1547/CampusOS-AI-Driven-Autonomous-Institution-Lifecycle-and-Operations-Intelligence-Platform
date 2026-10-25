{{--
    <x-ui.status-badge status="active" />           → green "Active"
    <x-ui.status-badge status="invited" label="Invitation sent" />
    Colours come from config/ui.php (status_tones).
--}}
@props(['status', 'label' => null, 'size' => 'md'])
@php
    $tone = config('ui.status_tones.'.$status, 'neutral');
    $text = $label ?? __(\Illuminate\Support\Str::of((string) $status)->replace('_', ' ')->ucfirst()->toString());
@endphp
<x-ui.badge :tone="$tone" :size="$size" dot {{ $attributes }}>{{ $text }}</x-ui.badge>
