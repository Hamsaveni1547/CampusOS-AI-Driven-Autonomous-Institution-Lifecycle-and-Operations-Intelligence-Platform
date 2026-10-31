{{--
    Published week grouped by day.
    <x-timetable.week :entries="$week['entries']" show="section|teacher|room" />
--}}
@props(['entries' => [], 'show' => 'teacher'])
@php
    $byDay = collect($entries)->groupBy('day_of_week')->sortKeys();
    $dayName = fn (int $day): string => now()->startOfWeek()->addDays($day - 1)->translatedFormat('l');
@endphp
@if ($byDay->isEmpty())
    <x-ui.empty-state compact icon="calendar-days" :title="__('No published timetable')" />
@else
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($byDay as $day => $periods)
            <x-ui.card wire:key="wd-{{ $day }}" :title="$dayName((int) $day)" :padding="false">
                @foreach ($periods as $period)
                    <div wire:key="wp-{{ $period['id'] }}" class="flex gap-3 border-b border-line px-5 py-2.5 last:border-0">
                        <span class="w-24 shrink-0 font-mono text-xs text-ink-muted">{{ $period['starts_at'] }}–{{ $period['ends_at'] }}</span>
                        <div class="min-w-0 text-sm">
                            <p class="font-medium text-ink">{{ $period['course']['code'] }} · {{ $period['course']['title'] }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ collect([
                                    $show !== 'section' ? $period['section'] : null,
                                    $show !== 'teacher' ? ($period['teacher']['name'] ?? null) : null,
                                    $show !== 'room' ? ($period['room']['code'] ?? null) : null,
                                    $period['component'] !== 'theory' ? \App\Support\Format::label($period['component']) : null,
                                ])->filter()->implode(' · ') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </x-ui.card>
        @endforeach
    </div>
@endif
