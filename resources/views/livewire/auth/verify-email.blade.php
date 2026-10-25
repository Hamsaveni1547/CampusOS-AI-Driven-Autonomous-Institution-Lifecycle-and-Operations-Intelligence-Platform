<div>
    <div class="mb-6">
        <div class="mb-4 flex size-11 items-center justify-center rounded-full bg-primary-soft text-primary-ink">
            <x-ui.icon name="mail" class="size-6" />
        </div>
        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Verify your email') }}</h1>
        <p class="mt-1.5 text-sm text-ink-muted">{{ __('We sent a 6-digit code to :email. Enter it below to activate your account.', ['email' => $email]) }}</p>
    </div>

    <form wire:submit="verify" class="space-y-5">
        <x-ui.otp-input wire:model="code" :label="__('Verification code')" x-on:otp-complete="$wire.verify()" />
        <x-ui.button type="submit" block size="lg" loading="verify">{{ __('Verify email') }}</x-ui.button>
    </form>

    <div x-data="{ now: Math.floor(Date.now() / 1000), at: @entangle('resendAvailableAt') }" x-init="setInterval(() => now = Math.floor(Date.now() / 1000), 1000)"
        class="mt-5 text-center text-sm text-ink-muted">
        <span x-show="now < at">{{ __('Didn\'t get it? You can resend in') }} <span class="tabular" x-text="at - now"></span>s</span>
        <button type="button" x-show="now >= at" x-cloak wire:click="resend" class="font-medium text-primary hover:underline">{{ __('Resend code') }}</button>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 border-t border-line pt-5 text-center">
        @csrf
        <button type="submit" class="text-sm text-ink-muted hover:text-ink">{{ __('Sign out') }}</button>
    </form>
</div>
