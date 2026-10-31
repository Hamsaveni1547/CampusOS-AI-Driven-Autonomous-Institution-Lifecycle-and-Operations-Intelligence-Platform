{{--
    One day's periods with that day's changes.
    <x-timetable.day :periods="$periods" :adjustable="false" />
    When adjustable, rows offer cancel / substitute / undo actions (the Livewire component provides openChange and undo).
--}}
@props(['periods' => [], 'adjustable' => false])
@php
    $tones = ['scheduled' => 'neutral', 'cancelled' => 'danger', 'substituted' => 'warning', 'handed_over' => 'warning', 'extra' => 'success'];
    $labels = ['scheduled' => null, 'cancelled' => __('Cancelled'), 'substituted' => __('Substitute'), 'handed_over' => __('Taken by a substitute'), 'extra' => __('Extra class')];
@endphp
@if ($periods === [])
    <p class="px-5 py-4 text-sm text-ink-muted">{{ __('No classes on this day.') }}</p>
@else
    @foreach ($periods as $period)
        <div wire:key="dp-{{ $period['entry_id'] ?? $period['adjustment_id'] }}" @class(['flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0', 'opacity-60' => $period['status'] === 'cancelled'])>
            <span class="w-24 shrink-0 font-mono text-xs text-ink-muted">{{ $period['starts_at'] }}–{{ $period['ends_at'] }}</span>
            <div class="min-w-0 flex-1 text-sm">
                <p @class(['font-medium text-ink', 'line-through' => $period['status'] === 'cancelled'])>{{ $period['course']['code'] }} · {{ $period['course']['title'] }}</p>
                <p class="text-xs text-ink-muted">{{ collect([$period['section'], $period['teacher'], $period['room']])->filter()->implode(' · ') }}@if ($period['reason']) · {{ $period['reason'] }}@endif</p>
            </div>
            @if ($labels[$period['status']] ?? null)<x-ui.badge size="sm" :tone="$tones[$period['status']] ?? 'neutral'">{{ $labels[$period['status']] }}</x-ui.badge>@endif
            @if ($adjustable)
                @if ($period['status'] === 'scheduled' && $period['entry_id'])
                    <x-ui.button size="sm" variant="ghost" wire:click="openChange('{{ $period['entry_id'] }}', 'substituted')">{{ __('Substitute') }}</x-ui.button>
                    <x-ui.button size="sm" variant="ghost" wire:click="openChange('{{ $period['entry_id'] }}', 'cancelled')">{{ __('Cancel') }}</x-ui.button>
                @elseif ($period['adjustment_id'])
                    <x-ui.button size="sm" variant="ghost" icon="rotate-ccw" wire:click="undo('{{ $period['adjustment_id'] }}')" wire:confirm="{{ __('Undo this change?') }}">{{ __('Undo') }}</x-ui.button>
                @endif
            @endif
        </div>
    @endforeach
@endif
