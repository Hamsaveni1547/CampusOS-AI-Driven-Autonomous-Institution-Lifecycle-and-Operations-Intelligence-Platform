{{--
    Base HTML document used by every layout. Sets the theme class before paint,
    loads the CSS/JS bundles and Livewire, and renders the toast stack.
--}}
@props(['title' => null, 'bodyClass' => ''])
@php
    $theme = request()->cookie('campus_theme', 'system');
    $institution = $currentUser?->institution();
    $appName = $institution['short_name'] ?? $institution['name'] ?? config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $theme === 'dark', 'h-full'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ $appName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (function () {
            var match = document.cookie.match(/(?:^|; )campus_theme=(light|dark|system)/);
            var mode = match ? match[1] : 'system';
            if (mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @livewireStyles(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
    {{ $head ?? '' }}
</head>
<body {{ $attributes->class(['h-full bg-canvas font-sans text-sm text-ink antialiased', $bodyClass]) }}>
    <a href="#main" class="sr-only z-[80] rounded-md bg-primary px-3 py-2 text-on-primary focus:not-sr-only focus:fixed focus:left-3 focus:top-3">
        {{ __('Skip to content') }}
    </a>

    {{ $slot }}

    <x-ui.toast-stack />

    @livewireScriptConfig(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>
