<x-layout.error status="419" icon="clock" :title="__('Page expired')" :message="$message ?? __('This page was open for too long. Refresh and try again.')" :trace-id="$traceId ?? null">
    <x-ui.button :href="url()->previous()" icon="refresh-cw">{{ __('Go back and retry') }}</x-ui.button>
</x-layout.error>
