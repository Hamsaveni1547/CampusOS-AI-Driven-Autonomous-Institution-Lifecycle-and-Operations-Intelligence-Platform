<div>
    <div class="mb-6">
        <div class="mb-4 flex size-11 items-center justify-center rounded-full bg-warning-soft text-warning">
            <x-ui.icon name="key-round" class="size-6" />
        </div>
        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Set a new password') }}</h1>
        <p class="mt-1.5 text-sm text-ink-muted">{{ __('For your security, choose a new password before continuing. You were given a temporary password, or your password has expired.') }}</p>
    </div>

    <form wire:submit="save" class="space-y-5" novalidate>
        <x-ui.password :label="__('Current (temporary) password')" wire:model="current_password" autocomplete="current-password" autofocus required />
        <x-ui.password :label="__('New password')" wire:model="password" strength="10" autocomplete="new-password" required />
        <x-ui.password :label="__('Confirm new password')" wire:model="password_confirmation" autocomplete="new-password" required />
        <x-ui.button type="submit" block size="lg" loading="save">{{ __('Save and continue') }}</x-ui.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 border-t border-line pt-5 text-center">
        @csrf
        <button type="submit" class="text-sm text-ink-muted hover:text-ink">{{ __('Sign out') }}</button>
    </form>
</div>
