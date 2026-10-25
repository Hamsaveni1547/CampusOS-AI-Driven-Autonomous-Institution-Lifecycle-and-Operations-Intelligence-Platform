{{-- <x-ui.divider /> or <x-ui.divider>or</x-ui.divider> --}}
@if (trim((string) $slot) === '')
    <hr {{ $attributes->class(['border-line']) }}>
@else
    <div {{ $attributes->class(['flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-ink-subtle']) }} role="separator">
        <span class="h-px flex-1 bg-line"></span>
        {{ $slot }}
        <span class="h-px flex-1 bg-line"></span>
    </div>
@endif
