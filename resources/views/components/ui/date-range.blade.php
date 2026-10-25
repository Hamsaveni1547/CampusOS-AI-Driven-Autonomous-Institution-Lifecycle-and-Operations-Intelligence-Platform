{{--
    Two dates side by side (Livewire property names in from/to).
    <x-ui.date-range label="Dates" from="form.starts_on" to="form.ends_on" />
    live: update the component on every change (for filters).
--}}
@props(['label' => null, 'from', 'to', 'hint' => null, 'live' => false])
<fieldset {{ $attributes->class(['min-w-0']) }}>
    @if ($label)
        <legend class="mb-1.5 text-sm font-medium text-ink">{{ $label }}</legend>
    @endif
    <div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-[1fr_auto_1fr]">
        @if ($live)
            <x-ui.date-picker :aria-label="__('From')" wire:model.live="{{ $from }}" />
            <span class="hidden pt-2 text-ink-subtle sm:block" aria-hidden="true">→</span>
            <x-ui.date-picker :aria-label="__('To')" wire:model.live="{{ $to }}" />
        @else
            <x-ui.date-picker :aria-label="__('From')" wire:model="{{ $from }}" />
            <span class="hidden pt-2 text-ink-subtle sm:block" aria-hidden="true">→</span>
            <x-ui.date-picker :aria-label="__('To')" wire:model="{{ $to }}" />
        @endif
    </div>
    @if ($hint)
        <p class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</fieldset>
