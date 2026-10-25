{{-- Light / dark / system switch (dropdown). --}}
<div x-data="dropdown" class="relative" x-on:keydown.escape.window="close()" {{ $attributes }}>
    <button type="button" x-ref="button" x-on:click="toggle()" :aria-expanded="open" aria-haspopup="menu"
        class="flex size-9 items-center justify-center rounded-md text-ink-muted transition hover:bg-surface-muted hover:text-ink"
        aria-label="{{ __('Theme') }}" title="{{ __('Theme') }}">
        <span x-show="$store.theme.mode === 'light'">{!! \App\Support\Icons::svg('sun', 'size-[18px]') !!}</span>
        <span x-show="$store.theme.mode === 'dark'" x-cloak>{!! \App\Support\Icons::svg('moon', 'size-[18px]') !!}</span>
        <span x-show="$store.theme.mode === 'system'" x-cloak>{!! \App\Support\Icons::svg('sun-moon', 'size-[18px]') !!}</span>
    </button>
    <div x-ref="panel" x-show="open" x-cloak x-transition.origin.top.right x-on:click.outside="close(false)"
        x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
        role="menu" class="absolute right-0 z-30 mt-2 w-40 rounded-lg border border-line bg-surface-raised p-1 shadow-lg">
        @foreach (['light' => ['Light', 'sun'], 'dark' => ['Dark', 'moon'], 'system' => ['System', 'monitor']] as $mode => [$label, $icon])
            <button type="button" role="menuitem" x-on:click="$store.theme.set('{{ $mode }}'); close()"
                class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-sm text-ink hover:bg-surface-muted focus:bg-surface-muted focus:outline-none">
                <x-ui.icon :name="$icon" class="size-4 text-ink-muted" />
                <span class="flex-1 text-left">{{ __($label) }}</span>
                <span x-show="$store.theme.mode === '{{ $mode }}'" class="text-primary">{!! \App\Support\Icons::svg('check', 'size-4') !!}</span>
            </button>
        @endforeach
    </div>
</div>
