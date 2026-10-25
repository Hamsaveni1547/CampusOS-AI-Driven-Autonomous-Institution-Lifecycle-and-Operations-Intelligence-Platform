@props(['breadcrumbs' => [], 'user' => null])
@php
    $institutions = $user?->institutions() ?? [];
    $current = $user?->institution();
    $route = fn (string $name, mixed $parameters = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $parameters) : null;
@endphp
<header class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-3 border-b border-line bg-surface/85 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" class="-ml-1 rounded-md p-2 text-ink-muted hover:bg-surface-muted hover:text-ink lg:hidden" x-on:click="openMobile()" aria-label="{{ __('Open menu') }}">
        <x-ui.icon name="menu" class="size-5" />
    </button>

    <div class="hidden min-w-0 flex-1 sm:block">
        <x-ui.breadcrumbs :items="$breadcrumbs" />
    </div>
    <div class="flex-1 sm:hidden"></div>

    <button type="button" x-on:click="$dispatch('open-command-palette')"
        class="hidden h-9 w-64 items-center gap-2 rounded-md border border-line bg-surface-muted/60 px-3 text-sm text-ink-subtle transition hover:border-line-strong md:flex">
        <x-ui.icon name="search" class="size-4" />
        <span class="flex-1 text-left">{{ __('Search or jump to…') }}</span>
        <kbd class="rounded border border-line bg-surface px-1.5 font-sans text-[11px] text-ink-muted">Ctrl K</kbd>
    </button>
    <button type="button" x-on:click="$dispatch('open-command-palette')" class="rounded-md p-2 text-ink-muted hover:bg-surface-muted md:hidden" aria-label="{{ __('Search') }}">
        <x-ui.icon name="search" class="size-5" />
    </button>

    @if (count($institutions) > 1 && ($switchUrl = $route('institution.switch')))
        <x-ui.dropdown align="right" width="w-72">
            <x-slot:trigger>
                <button type="button" class="hidden h-9 max-w-52 items-center gap-2 rounded-md border border-line px-2.5 text-sm text-ink hover:bg-surface-muted lg:flex">
                    <x-ui.icon name="building" class="size-4 text-ink-muted" />
                    <span class="truncate">{{ $current['short_name'] ?? $current['name'] ?? __('Institution') }}</span>
                    <x-ui.icon name="chevrons-up-down" class="size-3.5 text-ink-subtle" />
                </button>
            </x-slot:trigger>
            <p class="px-2.5 py-1.5 text-xs font-medium text-ink-subtle">{{ __('Switch institution') }}</p>
            @foreach ($institutions as $institution)
                <form method="POST" action="{{ $switchUrl }}">
                    @csrf
                    <input type="hidden" name="institution" value="{{ $institution['id'] }}">
                    <x-ui.dropdown-item type="submit" :icon="($current['id'] ?? null) === $institution['id'] ? 'check' : 'building-2'">
                        <span class="truncate">{{ $institution['name'] }}</span>
                    </x-ui.dropdown-item>
                </form>
            @endforeach
        </x-ui.dropdown>
    @endif

    @if ($user && $route('notifications.index'))
        <livewire:notification-bell />
    @endif

    <x-ui.theme-toggle />

    @if ($user)
        <x-ui.dropdown align="right" width="w-64">
            <x-slot:trigger>
                <button type="button" class="flex items-center gap-2 rounded-full p-0.5 ring-offset-2 ring-offset-surface hover:ring-2 hover:ring-line" aria-label="{{ __('Account menu') }}">
                    <x-ui.avatar :name="$user->name()" size="sm" />
                </button>
            </x-slot:trigger>
            <div class="border-b border-line px-3 py-2.5">
                <p class="truncate text-sm font-medium text-ink">{{ $user->name() }}</p>
                <p class="truncate text-xs text-ink-muted">{{ $user->email() }}</p>
            </div>
            <div class="py-1">
                @if ($url = $route('account.profile'))<x-ui.dropdown-item :href="$url" icon="user">{{ __('My profile') }}</x-ui.dropdown-item>@endif
                @if ($url = $route('account.security'))<x-ui.dropdown-item :href="$url" icon="shield-check">{{ __('Security') }}</x-ui.dropdown-item>@endif
                @if ($url = $route('account.notifications'))<x-ui.dropdown-item :href="$url" icon="bell">{{ __('Notification settings') }}</x-ui.dropdown-item>@endif
            </div>
            @if ($logoutUrl = $route('logout'))
                <div class="border-t border-line py-1">
                    <form method="POST" action="{{ $logoutUrl }}">
                        @csrf
                        <x-ui.dropdown-item type="submit" icon="log-out" tone="danger">{{ __('Sign out') }}</x-ui.dropdown-item>
                    </form>
                </div>
            @endif
        </x-ui.dropdown>
    @endif
</header>
