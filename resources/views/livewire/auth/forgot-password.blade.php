<div>
    @if ($sent)
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-success-soft text-success">
                <x-ui.icon name="mail" class="size-6" />
            </div>
            <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Check your email') }}</h1>
            <p class="mt-2 text-sm text-ink-muted">
                {{ __('If an account exists for :email, we sent a link to reset the password. The link is valid for 60 minutes.', ['email' => $email]) }}
            </p>
            <p class="mt-4 text-xs text-ink-subtle">{{ __('No email? Check your spam folder, or ask your administrator to reset your password.') }}</p>
            <div class="mt-6 flex flex-col gap-2">
                <x-ui.button :href="route('login')" block>{{ __('Back to sign in') }}</x-ui.button>
                <x-ui.button variant="ghost" block wire:click="$set('sent', false)">{{ __('Use a different email') }}</x-ui.button>
            </div>
        </div>
    @else
        <div class="mb-6">
            <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Forgot your password?') }}</h1>
            <p class="mt-1.5 text-sm text-ink-muted">{{ __('Enter the email of your account and we will send you a link to set a new password.') }}</p>
        </div>

        @if ($alert)
            <x-ui.alert tone="danger" class="mb-5">{{ $alert }}</x-ui.alert>
        @endif

        <form wire:submit="send" class="space-y-5" novalidate>
            <x-ui.input :label="__('Email')" type="email" wire:model="email" icon="mail" autocomplete="email" autofocus required />
            <x-ui.button type="submit" block size="lg" loading="send">{{ __('Send reset link') }}</x-ui.button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
            <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">{{ __('Back to sign in') }}</a>
        </p>
    @endif
</div>
