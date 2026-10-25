<div>
    <div class="mb-6">
        <x-ui.stepper :steps="[__('Method'), __('Confirm'), __('Verify'), __('Recovery codes')]"
            :current="['choose' => 1, 'password' => 2, 'code' => 3, 'recovery' => 4][$step]" class="mb-6" />
        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Set up two-step verification') }}</h1>
        <p class="mt-1.5 text-sm text-ink-muted">{{ __('Your role requires a second step when signing in. It keeps your account safe even if your password is stolen.') }}</p>
    </div>

    @switch($step)
        @case('choose')
            <div class="space-y-3">
                <button type="button" wire:click="choose('totp')" class="flex w-full items-start gap-4 rounded-lg border border-line p-4 text-left transition hover:border-primary hover:bg-primary-soft/40">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary-soft text-primary-ink"><x-ui.icon name="smartphone" class="size-5" /></span>
                    <span>
                        <span class="flex items-center gap-2 font-medium text-ink">{{ __('Authenticator app') }} <x-ui.badge tone="success" size="sm">{{ __('Recommended') }}</x-ui.badge></span>
                        <span class="mt-0.5 block text-sm text-ink-muted">{{ __('Google Authenticator, Microsoft Authenticator or similar. Works without internet on your phone.') }}</span>
                    </span>
                </button>
                <button type="button" wire:click="choose('email')" class="flex w-full items-start gap-4 rounded-lg border border-line p-4 text-left transition hover:border-primary hover:bg-primary-soft/40">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-surface-muted text-ink-muted"><x-ui.icon name="mail" class="size-5" /></span>
                    <span>
                        <span class="font-medium text-ink">{{ __('Email code') }}</span>
                        <span class="mt-0.5 block text-sm text-ink-muted">{{ __('We email you a code each time you sign in from a new device.') }}</span>
                    </span>
                </button>
            </div>
            @break

        @case('password')
            <form wire:submit="start" class="space-y-5" novalidate>
                <x-ui.password :label="__('Confirm your password')" wire:model="current_password" autocomplete="current-password" autofocus required />
                <div class="flex gap-2">
                    <x-ui.button variant="secondary" wire:click="back">{{ __('Back') }}</x-ui.button>
                    <x-ui.button type="submit" class="flex-1" loading="start">{{ $method === 'totp' ? __('Show QR code') : __('Email me a code') }}</x-ui.button>
                </div>
            </form>
            @break

        @case('code')
            <form wire:submit="confirm" class="space-y-5">
                @if ($method === 'totp')
                    <ol class="space-y-3 text-sm text-ink-muted">
                        <li><span class="font-medium text-ink">1.</span> {{ __('Open your authenticator app and add a new account.') }}</li>
                        <li><span class="font-medium text-ink">2.</span> {{ __('Scan this QR code, or enter the key by hand.') }}</li>
                    </ol>
                    <div class="flex flex-col items-center gap-3 rounded-lg border border-line bg-white p-4">
                        <div class="size-48 [&_svg]:size-full" aria-label="{{ __('QR code for your authenticator app') }}" role="img">{!! $qrSvg !!}</div>
                    </div>
                    <div class="text-center text-sm"><x-ui.code :value="$secret" /></div>
                    <p class="text-sm text-ink-muted"><span class="font-medium text-ink">3.</span> {{ __('Enter the 6-digit code the app shows.') }}</p>
                @else
                    <p class="text-sm text-ink-muted">{{ __('Enter the code we sent to :to.', ['to' => $sentTo]) }}</p>
                @endif

                <x-ui.otp-input wire:model="code" :label="__('Verification code')" x-on:otp-complete="$wire.confirm()" />
                <div class="flex gap-2">
                    <x-ui.button variant="secondary" wire:click="back">{{ __('Start over') }}</x-ui.button>
                    <x-ui.button type="submit" class="flex-1" loading="confirm">{{ __('Turn on') }}</x-ui.button>
                </div>
            </form>
            @break

        @case('recovery')
            <div class="space-y-5">
                <x-ui.alert tone="success" :title="__('Two-step verification is on')">
                    {{ __('Save these recovery codes somewhere safe. Each code works once if you lose your phone or cannot get email.') }}
                </x-ui.alert>

                @if ($recoveryCodes !== [])
                    <div x-data="{ codes: @js($recoveryCodes) }">
                        <ul class="grid grid-cols-2 gap-2 rounded-lg border border-line bg-surface-muted/50 p-4 font-mono text-sm text-ink">
                            @foreach ($recoveryCodes as $recoveryCode)
                                <li wire:key="rc-{{ $loop->index }}">{{ $recoveryCode }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.button size="sm" variant="secondary" icon="download"
                                x-on:click="const url = URL.createObjectURL(new Blob(['CampusOS recovery codes\n\n' + codes.join('\n') + '\n'], { type: 'text/plain' })); const a = document.createElement('a'); a.href = url; a.download = 'campusos-recovery-codes.txt'; a.click(); URL.revokeObjectURL(url)">
                                {{ __('Download') }}
                            </x-ui.button>
                            <x-ui.button size="sm" variant="secondary" icon="copy" x-on:click="navigator.clipboard.writeText(codes.join('\n')); $store.toasts.push({ type: 'success', title: @js(__('Copied')) })">{{ __('Copy') }}</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" icon="printer" x-on:click="window.print()">{{ __('Print') }}</x-ui.button>
                        </div>
                    </div>
                    <x-ui.checkbox wire:model="savedCodes" :label="__('I have saved my recovery codes')" />
                @endif

                <x-ui.button block size="lg" wire:click="finish" loading="finish">{{ __('Continue to CampusOS') }}</x-ui.button>
            </div>
            @break
    @endswitch

    <form method="POST" action="{{ route('logout') }}" class="mt-6 border-t border-line pt-5 text-center">
        @csrf
        <button type="submit" class="text-sm text-ink-muted hover:text-ink">{{ __('Sign out') }}</button>
    </form>
</div>
