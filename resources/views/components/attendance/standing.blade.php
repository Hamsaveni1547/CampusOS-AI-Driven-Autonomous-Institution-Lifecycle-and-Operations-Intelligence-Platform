{{-- <x-attendance.standing standing="condonable" /> --}}
@props(['standing'])
@php
    $map = [
        'ok' => ['success', __('OK')],
        'condoned' => ['info', __('Condoned')],
        'condonable' => ['warning', __('Can be condoned')],
        'shortage' => ['danger', __('Shortage')],
        'no_classes' => ['neutral', __('No classes yet')],
    ];
    [$tone, $label] = $map[$standing] ?? ['neutral', \App\Support\Format::label($standing)];
@endphp
<x-ui.badge size="sm" :tone="$tone">{{ $label }}</x-ui.badge>
