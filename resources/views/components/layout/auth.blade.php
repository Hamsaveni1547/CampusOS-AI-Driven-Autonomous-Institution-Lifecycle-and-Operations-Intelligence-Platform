{{--
    Layout for sign-in and account-recovery pages: centred card, institution branding.
    <x-layout.auth title="Sign in" heading="Welcome back" subheading="Sign in to continue">...</x-layout.auth>
--}}
@props(['title' => null, 'heading' => null, 'subheading' => null, 'wide' => false])
<x-layout.document :title="$title">
    <div class="relative flex min-h-full flex-col">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-80 bg-gradient-to-b from-primary-soft to-transparent" aria-hidden="true"></div>

        <header class="flex items-center justify-between px-4 py-5 sm:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-semibold text-ink">
                <span class="flex size-9 items-center justify-center rounded-md bg-primary text-on-primary shadow-sm">
                    <x-ui.icon name="graduation-cap" class="size-5" />
                </span>
                <span class="text-base tracking-tight">{{ config('app.name') }}</span>
            </a>
            <x-ui.theme-toggle />
        </header>

        <main id="main" class="flex flex-1 items-start justify-center px-4 pb-16 pt-6 sm:items-center sm:pt-0">
            <div @class(['w-full', 'max-w-md' => ! $wide, 'max-w-2xl' => $wide])>
                <div class="rounded-xl border border-line bg-surface p-6 shadow-lg sm:p-8 dark:shadow-none">
                    @if ($heading)
                        <div class="mb-6">
                            <h1 class="text-xl font-semibold tracking-tight text-ink">{{ $heading }}</h1>
                            @if ($subheading)
                                <p class="mt-1.5 text-sm text-ink-muted">{{ $subheading }}</p>
                            @endif
                        </div>
                    @endif
                    {{ $slot }}
                </div>
                {{ $below ?? '' }}
            </div>
        </main>

        <footer class="px-4 py-6 text-center text-xs text-ink-subtle">
            &copy; {{ date('Y') }} {{ config('app.name') }} · {{ __('Secure campus management') }}
        </footer>
    </div>
</x-layout.document>
