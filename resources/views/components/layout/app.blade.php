{{--
    Signed-in application shell: sidebar, top bar, content.
    <x-layout.app title="Departments" :breadcrumbs="['Academics' => null, 'Departments' => null]">
        <x-ui.page-header ... />
        ...
    </x-layout.app>
    width: full (tables) | narrow (forms, settings)
--}}
@props(['title' => null, 'breadcrumbs' => [], 'width' => 'full'])
@php
    $navigation = app(\App\Support\Navigation::class);
    $sections = $navigation->sections($currentUser);
    $palette = $navigation->paletteItems($currentUser);
@endphp
<x-layout.document :title="$title">
    <div x-data="sidebar" class="flex min-h-full" x-on:keydown.escape.window="closeMobile()">
        {{-- Mobile drawer backdrop --}}
        <div x-show="mobileOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" x-on:click="closeMobile()" aria-hidden="true"></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-line bg-surface transition-transform duration-200 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:translate-x-0"
            :class="{ 'translate-x-0': mobileOpen, 'lg:w-[4.5rem]': collapsed, 'lg:w-64': ! collapsed }"
            aria-label="{{ __('Main navigation') }}"
        >
            <x-layout.sidebar :sections="$sections" :user="$currentUser" />
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.topbar :breadcrumbs="$breadcrumbs" :user="$currentUser" />

            <main id="main" tabindex="-1" class="flex-1 px-4 py-6 focus:outline-none sm:px-6 lg:px-8 lg:py-8">
                <div @class(['mx-auto w-full', 'max-w-7xl' => $width === 'full', 'max-w-3xl' => $width === 'narrow', 'max-w-5xl' => $width === 'medium'])>
                    {{ $slot }}
                </div>
            </main>
        </div>

        <x-ui.command-palette :items="$palette" />
    </div>
</x-layout.document>
