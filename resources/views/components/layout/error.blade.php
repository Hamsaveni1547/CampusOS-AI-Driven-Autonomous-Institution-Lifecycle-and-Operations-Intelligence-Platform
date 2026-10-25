{{-- Layout for error pages (403, 404, 419, 429, 500, 503). --}}
@props(['status', 'title', 'message', 'icon' => 'triangle-alert', 'traceId' => null])
@php
    $home = \Illuminate\Support\Facades\Route::has('home') ? route('home') : url('/');
@endphp
<x-layout.document :title="$title">
    <main id="main" class="flex min-h-full items-center justify-center px-4 py-16">
        <div class="w-full max-w-lg text-center">
            <div class="mx-auto mb-6 flex size-14 items-center justify-center rounded-full bg-primary-soft text-primary-ink">
                <x-ui.icon :name="$icon" class="size-7" />
            </div>
            <p class="tabular text-sm font-semibold text-primary">{{ $status }}</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ $title }}</h1>
            <p class="mt-3 text-sm text-ink-muted">{{ $message }}</p>
            @if ($traceId)
                <p class="mt-4 text-xs text-ink-subtle">{{ __('Reference for support') }}: <x-ui.code :value="$traceId" /></p>
            @endif
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                {{ $slot }}
                <x-ui.button variant="secondary" :href="$home" icon="house">{{ __('Go to home') }}</x-ui.button>
            </div>
        </div>
    </main>
</x-layout.document>
