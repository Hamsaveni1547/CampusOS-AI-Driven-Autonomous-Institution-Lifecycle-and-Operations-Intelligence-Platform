{{--
    Aggregated survey results from the API: <x-feedback.results :results="$results" />
--}}
@props(['results'])
@if ($results['hidden'])
    <p {{ $attributes->class(['text-sm text-ink-muted']) }}>{{ __('Results appear once :m people have answered (now :n). This keeps answers anonymous.', ['m' => $results['minimum'], 'n' => $results['responses']]) }}</p>
@else
    <div {{ $attributes->class(['space-y-4']) }}>
        <p class="text-xs text-ink-muted">{{ trans_choice(':count response|:count responses', $results['responses']) }}</p>
        @foreach ($results['questions'] as $q)
            <div wire:key="fq-{{ $q['id'] }}" class="text-sm">
                <p class="mb-1 font-medium text-ink">{{ $q['text'] }}</p>
                @if ($q['type'] === 'rating')
                    <p class="mb-1 text-ink"><span class="text-lg font-semibold">{{ $q['average'] ?? '—' }}</span> <span class="text-xs text-ink-muted">/ 5</span></p>
                    @php($max = max(1, max($q['distribution'])))
                    @foreach (array_reverse($q['distribution'], true) as $stars => $count)
                        <div class="flex items-center gap-2 text-xs"><span class="w-4 text-ink-muted">{{ $stars }}</span><div class="h-2 flex-1 rounded bg-surface-muted"><div class="h-2 rounded bg-primary" style="width: {{ round($count * 100 / $max) }}%"></div></div><span class="w-6 text-right text-ink-muted">{{ $count }}</span></div>
                    @endforeach
                @elseif ($q['type'] === 'choice')
                    @foreach ($q['choices'] as $option => $count)
                        <div class="flex justify-between text-xs"><span>{{ $option }}</span><span class="font-medium">{{ $count }}</span></div>
                    @endforeach
                @else
                    @forelse ($q['comments'] as $comment)
                        <p class="mb-1 rounded bg-surface-muted px-3 py-2 text-xs text-ink">“{{ $comment }}”</p>
                    @empty
                        <p class="text-xs text-ink-muted">{{ __('No comments.') }}</p>
                    @endforelse
                @endif
            </div>
        @endforeach
    </div>
@endif
