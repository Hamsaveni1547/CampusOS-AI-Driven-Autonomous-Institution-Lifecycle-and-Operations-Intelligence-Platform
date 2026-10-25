<div>
    <div class="mb-6">
        <div class="mb-4 flex size-11 items-center justify-center rounded-full bg-primary-soft text-primary-ink">
            <x-ui.icon name="shield-check" class="size-6" />
        </div>
        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Two-step verification') }}</h1>
        <p class="mt-1.5 text-sm text-ink-muted">
            @switch($method)
                @case('totp')
                    {{ __('Open your authenticator app and enter the 6-digit code for CampusOS.') }}
                    @break
                @case('email')
                    {{ $sentTo ? __('Enter the code we sent to :to.', ['to' => $sentTo]) : __('We will email you a 6-digit code.') }}
                    @break
                @default
                    {{ __('Enter one of the recovery codes you saved when you set up two-step verification.') }}
            @endswitch
        </p>
        <p class="mt-1 text-xs text-ink-subtle">{{ __('Signing in as :login', ['login' => $login]) }}</p>
    </div>

    <form wire:submit="verify" class="space-y-5">
        @if ($method === 'recovery_code')
            <x-ui.input :label="__('Recovery code')" wire:model="code" placeholder="ABCDE-12345" autocomplete="off" autofocus class="font-mono" />
        @elseif ($method === 'email' && ! $sentTo)
            <x-ui.button variant="secondary" block icon="mail" wire:click="sendEmailCode" loading="sendEmailCode">{{ __('Email me a code') }}</x-ui.button>
            @error('code')<p class="text-xs text-danger">{{ $message }}</p>@enderror
        @else
            <x-ui.otp-input wire:model="code" :label="__('Verification code')" x-on:otp-complete="$wire.verify()" wire:key="otp-{{ $method }}" />
        @endif

        @if ($method === 'email' && $sentTo)
            <div x-data="{ now: Math.floor(Date.now() / 1000), at: @js($resendAvailableAt) }" x-init="setInterval(() => now = Math.floor(Date.now() / 1000), 1000)" class="text-sm text-ink-muted">
                <span x-show="now < at">{{ __('You can request a new code in') }} <span class="tabular" x-text="at - now"></span>s</span>
                <button type="button" x-show="now >= at" wire:click="sendEmailCode" class="font-medium text-primary hover:underline">{{ __('Send a new code') }}</button>
            </div>
        @endif

        @if ($method !== 'email' || $sentTo)
            <x-ui.checkbox wire:model="rememberDevice" :label="__('Remember this device')" :description="__('Skip this step on this browser for 30 days. Only on a device you trust.')" />
            <x-ui.button type="submit" block size="lg" loading="verify">{{ __('Verify and sign in') }}</x-ui.button>
        @endif
    </form>

    <div class="mt-6 border-t border-line pt-5">
        <p class="mb-2 text-xs font-medium text-ink-muted">{{ __('Other ways to verify') }}</p>
        <div class="flex flex-col gap-1">
            @if (in_array('totp', $methods, true) && $method !== 'totp')
                <button type="button" wire:click="useMethod('totp')" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-ink hover:bg-surface-muted">
                    <x-ui.icon name="smartphone" class="size-4 text-ink-muted" /> {{ __('Use your authenticator app') }}
                </button>
            @endif
            @if (in_array('email', $methods, true) && $method !== 'email')
                <button type="button" wire:click="useMethod('email')" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-ink hover:bg-surface-muted">
                    <x-ui.icon name="mail" class="size-4 text-ink-muted" /> {{ __('Get a code by email') }}
                </button>
            @endif
            @if ($method !== 'recovery_code')
                <button type="button" wire:click="useMethod('recovery_code')" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-ink hover:bg-surface-muted">
                    <x-ui.icon name="key-round" class="size-4 text-ink-muted" /> {{ __('Use a recovery code') }}
                </button>
            @endif
            <button type="button" wire:click="cancel" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-ink-muted hover:bg-surface-muted">
                <x-ui.icon name="arrow-left" class="size-4" /> {{ __('Back to sign in') }}
            </button>
        </div>
    </div>
</div>
