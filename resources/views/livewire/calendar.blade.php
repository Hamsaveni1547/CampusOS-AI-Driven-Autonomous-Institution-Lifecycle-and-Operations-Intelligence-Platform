@php
    $tones = ['holiday' => 'bg-success-soft text-success', 'examination' => 'bg-danger-soft text-danger', 'deadline' => 'bg-warning-soft text-warning', 'term_start' => 'bg-primary-soft text-primary-ink', 'term_end' => 'bg-primary-soft text-primary-ink', 'event' => 'bg-info-soft text-info', 'other' => 'bg-surface-muted text-ink-muted'];
    $today = now()->setTimezone($currentUser?->timezone() ?? 'Asia/Kolkata')->toDateString();
@endphp
<div>
    <x-ui.page-header :title="__('Academic calendar')" :description="__('Holidays, examinations, deadlines and events of your institution.')" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <x-ui.button variant="secondary" size="sm" square icon="chevron-left" :label="__('Previous month')" wire:click="previous" />
            <h2 class="min-w-40 text-center text-base font-semibold text-ink">{{ $start->translatedFormat('F Y') }}</h2>
            <x-ui.button variant="secondary" size="sm" square icon="chevron-right" :label="__('Next month')" wire:click="next" />
            <x-ui.button variant="ghost" size="sm" wire:click="today">{{ __('Today') }}</x-ui.button>
        </div>
        <x-ui.select wire:model.live="type" :options="$types" :placeholder="__('All types')" class="w-48" :aria-label="__('Event type')" />
    </div>

    <div wire:loading.class="opacity-60" wire:target="previous,next,today,type" class="grid gap-6 lg:grid-cols-3">
        {{-- Month grid (desktop) --}}
        <x-ui.card :padding="false" class="hidden lg:col-span-2 lg:block">
            <div class="grid grid-cols-7 border-b border-line bg-surface-muted/60 text-center text-xs font-medium text-ink-muted">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                    <div class="py-2">{{ __($weekday) }}</div>
                @endforeach
            </div>
            @foreach ($grid as $week)
                <div class="grid grid-cols-7 border-b border-line last:border-0">
                    @foreach ($week as $date)
                        @php($key = $date->toDateString())
                        <div wire:key="d-{{ $key }}" @class(['min-h-24 border-r border-line p-1.5 last:border-r-0', 'bg-surface-muted/40' => $date->month !== $start->month])>
                            <p @class([
                                'tabular mb-1 flex size-6 items-center justify-center rounded-full text-xs',
                                'bg-primary font-semibold text-on-primary' => $key === $today,
                                'text-ink-subtle' => $date->month !== $start->month,
                                'text-ink' => $date->month === $start->month && $key !== $today,
                            ])>{{ $date->day }}</p>
                            @foreach (array_slice($days[$key] ?? [], 0, 3) as $event)
                                <p class="mb-0.5 truncate rounded px-1.5 py-0.5 text-[11px] font-medium {{ $tones[$event['type']] ?? $tones['other'] }}" title="{{ $event['title'] }}">{{ $event['title'] }}</p>
                            @endforeach
                            @if (count($days[$key] ?? []) > 3)
                                <p class="px-1.5 text-[11px] text-ink-muted">+{{ count($days[$key]) - 3 }} {{ __('more') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </x-ui.card>

        {{-- Agenda (all screens) --}}
        <x-ui.card :title="__('This month')" :padding="false">
            @forelse ($days as $date => $dayEvents)
                <div wire:key="a-{{ $date }}" class="border-b border-line px-5 py-3 last:border-0">
                    <p @class(['text-xs font-semibold uppercase tracking-wide', 'text-primary' => $date === $today, 'text-ink-muted' => $date !== $today])>
                        {{ \App\Support\Format::date($date, 'D, d M') }}
                    </p>
                    <ul class="mt-1.5 space-y-1.5">
                        @foreach ($dayEvents as $event)
                            <li class="flex items-start gap-2 text-sm">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full {{ explode(' ', $tones[$event['type']] ?? $tones['other'])[0] }} ring-2 ring-inset ring-current {{ explode(' ', $tones[$event['type']] ?? $tones['other'])[1] }}"></span>
                                <span class="min-w-0">
                                    <span class="font-medium text-ink">{{ $event['title'] }}</span>
                                    @if ($event['description'])<span class="block text-xs text-ink-muted">{{ $event['description'] }}</span>@endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <x-ui.empty-state compact icon="calendar" :title="__('No events this month')" />
            @endforelse
        </x-ui.card>
    </div>
</div>
