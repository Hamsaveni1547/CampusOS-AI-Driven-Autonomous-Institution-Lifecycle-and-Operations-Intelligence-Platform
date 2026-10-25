<x-layout.error status="429" icon="clock" :title="__('Too many attempts')" :message="$message ?? __('Please wait a moment before trying again.')" :trace-id="$traceId ?? null">

</x-layout.error>
