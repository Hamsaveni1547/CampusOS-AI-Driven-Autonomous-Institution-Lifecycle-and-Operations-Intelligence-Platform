@props(['href', 'icon', 'active' => false, 'badge' => null])
<a href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    :title="collapsed ? @js(trim((string) $slot)) : null"
    {{ $attributes->class([
        'group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition',
        'bg-primary-soft text-primary-ink' => $active,
        'text-ink-muted hover:bg-surface-muted hover:text-ink' => ! $active,
    ]) }}>
    <x-ui.icon :name="$icon" @class(['size-[18px]', 'text-primary' => $active, 'text-ink-subtle group-hover:text-ink-muted' => ! $active]) />
    <span class="truncate" x-show="! collapsed">{{ $slot }}</span>
    @if ($badge)
        <span class="ml-auto rounded-full bg-primary px-1.5 text-[11px] font-semibold text-on-primary" x-show="! collapsed">{{ $badge }}</span>
    @endif
</a>
