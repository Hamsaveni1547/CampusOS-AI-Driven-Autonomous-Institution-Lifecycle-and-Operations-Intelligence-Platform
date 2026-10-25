@props(['label'])
<div {{ $attributes->class(['min-w-0']) }}>
    <dt class="text-xs font-medium text-ink-muted">{{ $label }}</dt>
    <dd class="mt-1 break-words text-sm text-ink">{{ $slot }}</dd>
</div>
