<div>
    @if ($linkInvalid)
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-warning-soft text-warning">
                <x-ui.icon name="link" class="size-6" />
            </div>
            <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('This link has expired') }}</h1>
            <p class="mt-2 text-sm text-ink-muted">
                {{ $isAccountSetup
                    ? __('Invitation links are valid for 3 days and work once. Ask your administrator to resend the invitation, or request a new link below.')
                    : __('Reset links are valid for 60 minutes and work once. Request a new one to continue.') }}
            </p>
            <div class="mt-6 flex flex-col gap-2">
                <x-ui.button :href="route('password.forgot')" block>{{ __('Request a new link') }}</x-ui.button>
                <x-ui.button :href="route('login')" variant="ghost" block>{{ __('Back to sign in') }}</x-ui.button>
            </div>
        </div>
    @else
        <div class="mb-6">
            <h1 class="text-xl font-semibold tracking-tight text-ink">{{ $isAccountSetup ? __('Welcome to CampusOS') : __('Set a new password') }}</h1>
            <p class="mt-1.5 text-sm text-ink-muted">
                {{ $isAccountSetup ? __('Choose a password to finish setting up your account.') : __('Choose a new password for your account.') }}
            </p>
            <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-surface-muted px-2.5 py-1 text-xs text-ink-muted">
                <x-ui.icon name="mail" class="size-3.5" /> {{ $email }}
            </p>
        </div>

        @if ($alert)
            <x-ui.alert tone="danger" class="mb-5">{{ $alert }}</x-ui.alert>
        @endif

        <form wire:submit="save" class="space-y-5" novalidate>
            <x-ui.password :label="__('New password')" wire:model="password" strength="10" autocomplete="new-password" autofocus required />
            <x-ui.password :label="__('Confirm new password')" wire:model="password_confirmation" autocomplete="new-password" required />
            <x-ui.button type="submit" block size="lg" loading="save">{{ $isAccountSetup ? __('Create password and continue') : __('Change password') }}</x-ui.button>
        </form>
    @endif
</div>
