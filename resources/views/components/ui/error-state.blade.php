{{--
    <x-ui.error-state title="Couldn't load staff" :message="$error" :trace-id="$traceId">
        <x-ui.button variant="secondary" wire:click="$refresh" icon="refresh-cw">Try again</x-ui.button>
    </x-ui.error-state>
--}}
@props(['title' => null, 'message' => null, 'traceId' => null, 'icon' => 'triangle-alert'])
<div {{ $attributes->class(['flex flex-col items-center px-6 py-12 text-center']) }} role="alert">
    <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-danger-soft text-danger">
        <x-ui.icon :name="$icon" class="size-6" />
    </div>
    <h3 class="text-sm font-semibold text-ink">{{ $title ?? __('Something went wrong') }}</h3>
    <p class="mt-1 max-w-md text-sm text-ink-muted">{{ $message ?? __('Please try again. If the problem continues, contact support.') }}</p>
    @if ($traceId)
        <p class="mt-3 text-xs text-ink-subtle">{{ __('Reference') }}: <x-ui.code :value="$traceId" /></p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
