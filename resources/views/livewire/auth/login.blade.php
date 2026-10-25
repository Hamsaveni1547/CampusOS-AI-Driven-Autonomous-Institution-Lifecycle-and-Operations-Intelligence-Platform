<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Sign in to CampusOS') }}</h1>
        <p class="mt-1.5 text-sm text-ink-muted">{{ __('Use your institution email, username or mobile number.') }}</p>
    </div>

    @if ($alert)
        <x-ui.alert :tone="$alertTone" class="mb-5">{{ $alert }}</x-ui.alert>
    @endif

    <form wire:submit="authenticate" class="space-y-5" novalidate>
        <x-ui.input
            :label="__('Email, username or mobile')"
            wire:model="login"
            icon="user"
            autocomplete="username"
            autofocus
            required
        />

        <div>
            <x-ui.password :label="__('Password')" wire:model="password" autocomplete="current-password" required />
            <div class="mt-2 text-right">
                <a href="{{ route('password.forgot') }}" class="text-sm font-medium text-primary hover:underline">{{ __('Forgot password?') }}</a>
            </div>
        </div>

        <x-ui.button type="submit" block size="lg" loading="authenticate">{{ __('Sign in') }}</x-ui.button>
    </form>

    <p class="mt-6 flex items-center justify-center gap-1.5 text-xs text-ink-subtle">
        <x-ui.icon name="shield-check" class="size-3.5" />
        {{ __('Protected by two-step verification for staff accounts.') }}
    </p>
</div>
