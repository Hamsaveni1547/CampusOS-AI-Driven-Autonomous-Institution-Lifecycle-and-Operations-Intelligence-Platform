<x-layout.error status="503" icon="server" :title="__('CampusOS is temporarily unavailable')" :message="$message ?? __('We are having trouble reaching the CampusOS service. Please try again in a moment.')" :trace-id="$traceId ?? null">
    <x-ui.button :href="url()->current()" icon="refresh-cw">{{ __('Try again') }}</x-ui.button>
</x-layout.error>
