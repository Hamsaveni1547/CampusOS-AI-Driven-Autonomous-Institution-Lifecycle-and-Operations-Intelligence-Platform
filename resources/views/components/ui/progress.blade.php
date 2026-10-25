{{-- <x-ui.progress :value="64" label="Import progress" tone="primary" /> (value 0–100; alpine: x-bind:style on the bar) --}}
@props(['value' => 0, 'label' => null, 'tone' => 'primary', 'showValue' => false])
@php
    $bars = ['primary' => 'bg-primary', 'success' => 'bg-success', 'warning' => 'bg-warning', 'danger' => 'bg-danger'];
    $value = max(0, min(100, (float) $value));
@endphp
<div {{ $attributes->class(['w-full']) }}>
    @if ($label || $showValue)
        <div class="mb-1.5 flex justify-between text-xs text-ink-muted">
            <span>{{ $label }}</span>
            @if ($showValue)<span class="tabular">{{ round($value) }}%</span>@endif
        </div>
    @endif
    <div class="h-2 overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($value) }}" @if ($label) aria-label="{{ $label }}" @endif>
        <div class="h-full rounded-full transition-[width] duration-300 {{ $bars[$tone] ?? $bars['primary'] }}" style="width: {{ $value }}%"></div>
    </div>
</div>
