{{-- Joined buttons (segmented control). <x-ui.button-group><x-ui.button variant="secondary">Day</x-ui.button>…</x-ui.button-group> --}}
<div {{ $attributes->class(['inline-flex rounded-md shadow-xs [&>*]:rounded-none [&>*]:shadow-none [&>*:first-child]:rounded-l-md [&>*:last-child]:rounded-r-md [&>*+*]:-ml-px']) }} role="group">
    {{ $slot }}
</div>
