{{--
    Activity / audit list.
    <x-ui.timeline :items="[
        ['title' => 'Signed in', 'description' => 'Chrome on Windows · 10.0.0.4', 'time' => '2026-10-26T09:12:00+05:30', 'icon' => 'log-in', 'tone' => 'success'],
    ]" />
--}}
@props(['items' => []])
@php
    $tones = ['neutral' => 'bg-surface-muted text-ink-muted', 'primary' => 'bg-primary-soft text-primary-ink', 'success' => 'bg-success-soft text-success', 'warning' => 'bg-warning-soft text-warning', 'danger' => 'bg-danger-soft text-danger', 'info' => 'bg-info-soft text-info'];
@endphp
<ol {{ $attributes->class(['relative space-y-5']) }}>
    @foreach ($items as $item)
        <li class="relative flex gap-3">
            @unless ($loop->last)
                <span class="absolute left-4 top-9 -bottom-5 w-px bg-line" aria-hidden="true"></span>
            @endunless
            <span class="relative flex size-8 shrink-0 items-center justify-center rounded-full ring-4 ring-surface {{ $tones[$item['tone'] ?? 'neutral'] ?? $tones['neutral'] }}">
                <x-ui.icon :name="$item['icon'] ?? 'activity'" class="size-4" />
            </span>
            <div class="min-w-0 flex-1 pt-1">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <p class="text-sm font-medium text-ink">{{ $item['title'] }}</p>
                    @if (! empty($item['time']))
                        <x-ui.time :value="$item['time']" class="text-xs text-ink-subtle" />
                    @endif
                </div>
                @if (! empty($item['description']))
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $item['description'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
