<x-layout.error status="500" icon="triangle-alert" :title="__('Something went wrong')" :message="$message ?? __('We could not complete your request. Please try again; if it keeps happening, contact support with the reference below.')" :trace-id="$traceId ?? null">
    <x-ui.button :href="url()->current()" icon="refresh-cw">{{ __('Try again') }}</x-ui.button>
</x-layout.error>
