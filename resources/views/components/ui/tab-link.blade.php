{{-- Server-side tab (a link). Wrap several in <nav class="flex gap-5 border-b border-line">. --}}
@props(['href', 'active' => false, 'count' => null])
<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        '-mb-px flex shrink-0 items-center gap-2 border-b-2 px-0.5 pb-3 pt-1 text-sm font-medium transition',
        'border-primary text-primary-ink' => $active,
        'border-transparent text-ink-muted hover:border-line-strong hover:text-ink' => ! $active,
    ]) }}>
    {{ $slot }}
    @if ($count !== null)
        <span class="tabular rounded-full bg-surface-muted px-1.5 text-xs text-ink-muted">{{ $count }}</span>
    @endif
</a>
