{{--
    <x-ai.feedback-insights :insights="$insights" /> — AI summary of feedback comments: a sentiment bar, themes and a suggestion.
--}}
@props(['insights'])
@php
    $s = $insights['sentiment'];
    $total = max(1, $s['positive'] + $s['neutral'] + $s['negative']);
    $pct = fn (int $n) => round($n * 100 / $total);
    $tones = ['positive' => 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300', 'mixed' => 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300', 'negative' => 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300'];
    $maxMentions = max(1, collect($insights['themes'])->max('mentions'));
@endphp
<div {{ $attributes->class(['space-y-4']) }}>
    <p class="text-sm text-ink">{{ $insights['summary'] }}</p>

    <div>
        <div class="mb-1 flex justify-between text-xs text-ink-muted"><span>{{ __('Mood of :n comments', ['n' => $insights['comments']]) }}</span></div>
        <div class="flex h-3 overflow-hidden rounded-full bg-surface-muted" role="img" aria-label="{{ __(':p% positive, :u% neutral, :n% negative', ['p' => $pct($s['positive']), 'u' => $pct($s['neutral']), 'n' => $pct($s['negative'])]) }}">
            <span class="bg-emerald-500" style="width: {{ $pct($s['positive']) }}%"></span>
            <span class="bg-slate-400" style="width: {{ $pct($s['neutral']) }}%"></span>
            <span class="bg-rose-500" style="width: {{ $pct($s['negative']) }}%"></span>
        </div>
        <div class="mt-1 flex gap-4 text-xs text-ink-muted">
            <span><span class="mr-1 inline-block size-2 rounded-full bg-emerald-500"></span>{{ __('Positive :p%', ['p' => $pct($s['positive'])]) }}</span>
            <span><span class="mr-1 inline-block size-2 rounded-full bg-slate-400"></span>{{ __('Neutral :p%', ['p' => $pct($s['neutral'])]) }}</span>
            <span><span class="mr-1 inline-block size-2 rounded-full bg-rose-500"></span>{{ __('Negative :p%', ['p' => $pct($s['negative'])]) }}</span>
        </div>
    </div>

    <div>
        <p class="mb-2 text-xs font-medium uppercase text-ink-subtle">{{ __('What students talk about') }}</p>
        <div class="space-y-1.5">
            @foreach ($insights['themes'] as $t)
                <div wire:key="th-{{ $loop->index }}" class="flex items-center gap-2 text-sm">
                    <span class="w-48 shrink-0 truncate rounded-md border px-2 py-0.5 text-xs {{ $tones[$t['tone']] ?? $tones['mixed'] }}">{{ $t['theme'] }}</span>
                    <span class="h-2 rounded-full bg-violet-400" style="width: {{ max(4, round($t['mentions'] * 100 / $maxMentions)) }}%"></span>
                    <span class="text-xs text-ink-muted">{{ $t['mentions'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <x-ui.alert tone="info" :title="__('Suggestion')">{{ $insights['suggestion'] }}</x-ui.alert>
    <p class="text-[11px] text-ink-subtle">{{ __('Written by AI from anonymous comments. Read the comments before acting on it.') }}</p>
</div>
