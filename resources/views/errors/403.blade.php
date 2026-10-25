<x-layout.error status="403" icon="lock" :title="__('You don\'t have access')" :message="$message ?? __('Your role does not allow this page. If you think this is a mistake, ask your administrator.')" :trace-id="$traceId ?? null">
</x-layout.error>
