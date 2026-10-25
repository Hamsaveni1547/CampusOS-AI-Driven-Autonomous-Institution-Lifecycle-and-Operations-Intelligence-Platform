@props(['sections' => [], 'user' => null])
@php
    $institution = $user?->institution();
@endphp
<div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-line px-4">
    <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary text-on-primary shadow-sm">
        <x-ui.icon name="graduation-cap" class="size-5" />
    </span>
    <div class="min-w-0 flex-1" x-show="! collapsed" x-transition.opacity>
        <p class="truncate text-sm font-semibold text-ink">{{ $institution['short_name'] ?? $institution['name'] ?? config('app.name') }}</p>
        <p class="truncate text-xs text-ink-muted">{{ $institution ? ($institution['name'] ?? '') : __('Platform') }}</p>
    </div>
    <button type="button" class="ml-auto rounded-md p-1.5 text-ink-muted hover:bg-surface-muted hover:text-ink lg:hidden" x-on:click="closeMobile()" aria-label="{{ __('Close menu') }}">
        <x-ui.icon name="x" class="size-5" />
    </button>
</div>

<nav class="flex-1 overflow-y-auto px-3 py-4">
    @forelse ($sections as $section)
        <div @class(['mb-5' => ! $loop->last])>
            <p class="mb-1.5 px-2.5 text-[11px] font-semibold uppercase tracking-wider text-ink-subtle" x-show="! collapsed">{{ $section['label'] }}</p>
            <ul class="space-y-0.5">
                @foreach ($section['items'] as $item)
                    <li>
                        <x-layout.nav-item :href="$item['url']" :icon="$item['icon']" :active="$item['active']">{{ $item['label'] }}</x-layout.nav-item>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="px-2.5 text-sm text-ink-muted">{{ __('No menu items yet.') }}</p>
    @endforelse
</nav>

<div class="hidden shrink-0 border-t border-line p-3 lg:block">
    <button type="button" x-on:click="toggleCollapsed()"
        class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm text-ink-muted hover:bg-surface-muted hover:text-ink"
        :aria-label="collapsed ? @js(__('Expand sidebar')) : @js(__('Collapse sidebar'))">
        <span x-show="! collapsed">{!! \App\Support\Icons::svg('panel-left-close', 'size-[18px]') !!}</span>
        <span x-show="collapsed" x-cloak>{!! \App\Support\Icons::svg('panel-left', 'size-[18px]') !!}</span>
        <span x-show="! collapsed">{{ __('Collapse') }}</span>
    </button>
</div>
