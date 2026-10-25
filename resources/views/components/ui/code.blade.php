{{-- Monospace value with copy button: <x-ui.code value="01JBX6Q3K8…" /> ; copy=false to disable. --}}
@props(['value', 'copy' => true])
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-sm bg-surface-muted px-1.5 py-0.5 font-mono text-xs text-ink']) }}
    @if ($copy) x-data="clipboard(@js((string) $value))" @endif>
    <span class="break-all">{{ $value }}</span>
    @if ($copy)
        <button type="button" x-on:click="copy()" class="text-ink-subtle hover:text-ink" :aria-label="copied ? @js(__('Copied')) : @js(__('Copy'))">
            <span x-show="!copied">{!! \App\Support\Icons::svg('copy', 'size-3.5') !!}</span>
            <span x-show="copied" x-cloak class="text-success">{!! \App\Support\Icons::svg('check', 'size-3.5') !!}</span>
        </button>
    @endif
</span>
