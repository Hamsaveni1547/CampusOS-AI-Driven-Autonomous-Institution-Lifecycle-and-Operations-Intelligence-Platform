@php
    $eventLabels = [
        'login_succeeded' => [__('Signed in'), 'log-in', 'success'],
        'login_failed' => [__('Failed sign-in attempt'), 'circle-x', 'danger'],
        'login_blocked' => [__('Sign-in blocked'), 'ban', 'danger'],
        'account_locked' => [__('Account locked'), 'lock', 'danger'],
        'logout' => [__('Signed out'), 'log-out', 'neutral'],
        'password_changed' => [__('Password changed'), 'key-round', 'warning'],
        'password_reset_completed' => [__('Password reset'), 'key-round', 'warning'],
        'mfa_method_added' => [__('Two-step method added'), 'shield-check', 'primary'],
        'mfa_method_removed' => [__('Two-step method removed'), 'shield-alert', 'warning'],
        'mfa_challenge_passed' => [__('Two-step verification passed'), 'shield-check', 'success'],
        'mfa_challenge_failed' => [__('Wrong two-step code'), 'shield-alert', 'danger'],
        'session_revoked' => [__('Device signed out'), 'monitor', 'neutral'],
        'recovery_code_used' => [__('Recovery code used'), 'key-round', 'warning'],
    ];
    $methodLabels = ['totp' => [__('Authenticator app'), 'smartphone'], 'email' => [__('Email code'), 'mail']];
    $types = array_column($mfa['methods'] ?? [], 'type');
@endphp
<div>
    <x-ui.page-header :title="__('My account')">
        <x-slot:tabs><x-layout.account-tabs /></x-slot:tabs>
    </x-ui.page-header>

    <div class="space-y-6">
        {{-- Two-step verification --}}
        <x-ui.card :title="__('Two-step verification')" :description="__('A code from your phone or email when signing in on a new device.')">
            <x-slot:actions>
                @if ($mfa['enabled'])
                    <x-ui.badge tone="success" icon="shield-check">{{ __('On') }}</x-ui.badge>
                @else
                    <x-ui.badge tone="warning">{{ __('Off') }}</x-ui.badge>
                @endif
            </x-slot:actions>

            <div class="space-y-3">
                @foreach ($mfa['methods'] as $method)
                    @continue(! $method['is_confirmed'])
                    <div wire:key="m-{{ $method['id'] }}" class="flex flex-wrap items-center gap-3 rounded-lg border border-line p-3.5">
                        <span class="flex size-9 items-center justify-center rounded-md bg-surface-muted text-ink-muted"><x-ui.icon :name="$methodLabels[$method['type']][1] ?? 'shield'" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2 text-sm font-medium text-ink">
                                {{ $methodLabels[$method['type']][0] ?? $method['type'] }}
                                @if ($method['is_primary'])<x-ui.badge tone="primary" size="sm">{{ __('Default') }}</x-ui.badge>@endif
                            </p>
                            <p class="text-xs text-ink-muted">
                                {{ $method['destination'] ?? '' }}
                                @if ($method['last_used_at']) · {{ __('Last used') }} <x-ui.time :value="$method['last_used_at']" /> @endif
                            </p>
                        </div>
                        @unless ($method['is_primary'])
                            <x-ui.button size="sm" variant="ghost" wire:click="makePrimary('{{ $method['id'] }}')">{{ __('Make default') }}</x-ui.button>
                        @endunless
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="askRemove('{{ $method['id'] }}')">{{ __('Remove') }}</x-ui.button>
                    </div>
                @endforeach

                <div class="flex flex-wrap gap-2">
                    @unless (in_array('totp', array_column(array_filter($mfa['methods'], fn ($m) => $m['is_confirmed']), 'type'), true))
                        <x-ui.button variant="secondary" icon="smartphone" wire:click="openSetup('totp')">{{ __('Add authenticator app') }}</x-ui.button>
                    @endunless
                    @unless (in_array('email', array_column(array_filter($mfa['methods'], fn ($m) => $m['is_confirmed']), 'type'), true))
                        <x-ui.button variant="secondary" icon="mail" wire:click="openSetup('email')">{{ __('Add email code') }}</x-ui.button>
                    @endunless
                </div>

                @if ($mfa['enabled'])
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-surface-muted/50 px-4 py-3 text-sm">
                        <span class="text-ink-muted">{{ trans_choice('{0} No recovery codes left|{1} 1 recovery code left|[2,*] :count recovery codes left', $mfa['recovery_codes_remaining'], ['count' => $mfa['recovery_codes_remaining']]) }}</span>
                        <x-ui.button size="sm" variant="ghost" icon="refresh-cw" x-on:click="$dispatch('open-dialog', 'regenerate-codes')">{{ __('New recovery codes') }}</x-ui.button>
                    </div>
                @endif
            </div>
        </x-ui.card>

        {{-- Password --}}
        <x-ui.card :title="__('Password')" :description="__('Changing it signs you out of every other device.')">
            <form wire:submit="changePassword" class="grid gap-4 sm:max-w-md">
                <x-ui.password :label="__('Current password')" wire:model="current_password" autocomplete="current-password" />
                <x-ui.password :label="__('New password')" wire:model="password" strength="10" autocomplete="new-password" />
                <x-ui.password :label="__('Confirm new password')" wire:model="password_confirmation" autocomplete="new-password" />
                <div><x-ui.button type="submit" loading="changePassword">{{ __('Change password') }}</x-ui.button></div>
            </form>
        </x-ui.card>

        {{-- Sessions --}}
        <x-ui.card :title="__('Signed-in devices')" :padding="false">
            <x-slot:actions>
                @if (count($sessions) > 1)
                    <x-ui.button size="sm" variant="secondary" x-on:click="$dispatch('open-dialog', 'sign-out-others')">{{ __('Sign out other devices') }}</x-ui.button>
                @endif
            </x-slot:actions>
            @foreach ($sessions as $session)
                <div wire:key="s-{{ $session['id'] }}" class="flex items-center gap-3 border-b border-line px-5 py-3.5 last:border-0">
                    <x-ui.icon :name="str_contains((string) $session['device_name'], 'Android') || str_contains((string) $session['device_name'], 'iOS') ? 'smartphone' : 'monitor'" class="size-5 text-ink-muted" />
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 text-sm font-medium text-ink">
                            {{ $session['device_name'] }}
                            @if ($session['is_current'])<x-ui.badge tone="success" size="sm">{{ __('This device') }}</x-ui.badge>@endif
                        </p>
                        <p class="text-xs text-ink-muted">{{ $session['last_used_ip'] ?? $session['ip_address'] }} · {{ __('Active') }} <x-ui.time :value="$session['last_used_at'] ?? $session['created_at']" /></p>
                    </div>
                    @unless ($session['is_current'])
                        <x-ui.button size="sm" variant="ghost" wire:click="revokeSession('{{ $session['id'] }}')" wire:confirm="{{ __('Sign out this device?') }}">{{ __('Sign out') }}</x-ui.button>
                    @endunless
                </div>
            @endforeach
        </x-ui.card>

        @if ($devices !== [])
            <x-ui.card :title="__('Remembered devices')" :description="__('These skip two-step verification until they expire.')" :padding="false">
                @foreach ($devices as $device)
                    <div wire:key="t-{{ $device['id'] }}" class="flex items-center gap-3 border-b border-line px-5 py-3.5 last:border-0">
                        <x-ui.icon name="shield-check" class="size-5 text-ink-muted" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ $device['name'] ?? __('Device') }}</p>
                            <p class="text-xs text-ink-muted">{{ __('Until :date', ['date' => \App\Support\Format::date($device['expires_at'])]) }}</p>
                        </div>
                        <x-ui.button size="sm" variant="ghost" wire:click="forgetDevice('{{ $device['id'] }}')">{{ __('Forget') }}</x-ui.button>
                    </div>
                @endforeach
            </x-ui.card>
        @endif

        {{-- History --}}
        <x-ui.card :title="__('Recent activity')">
            @if ($events === [])
                <p class="text-sm text-ink-muted">{{ __('No activity yet.') }}</p>
            @else
                <x-ui.timeline :items="collect($events)->map(fn ($event) => [
                    'title' => $eventLabels[$event['event']][0] ?? \App\Support\Format::label($event['event']),
                    'icon' => $eventLabels[$event['event']][1] ?? 'activity',
                    'tone' => $eventLabels[$event['event']][2] ?? 'neutral',
                    'description' => trim(implode(' · ', array_filter([\App\Auth\DeviceName::fromUserAgent($event['user_agent']), $event['ip_address']]))),
                    'time' => $event['created_at'],
                ])->all()" />
            @endif
        </x-ui.card>
    </div>

    {{-- Dialogs --}}
    <x-ui.modal name="mfa-setup" :title="$setupMethod === 'totp' ? __('Add authenticator app') : __('Add email code')">
        @if ($setupStep === 'password')
            <form wire:submit="startSetup" class="space-y-4" id="mfa-setup-form">
                <x-ui.password :label="__('Confirm your password')" wire:model="setupPassword" autocomplete="current-password" />
            </form>
        @else
            <form wire:submit="confirmSetup" class="space-y-4" id="mfa-setup-form">
                @if ($setupMethod === 'totp' && $qrSvg)
                    <p class="text-sm text-ink-muted">{{ __('Scan the QR code with your authenticator app, then enter the 6-digit code.') }}</p>
                    <div class="mx-auto size-44 rounded-lg bg-white p-2 [&_svg]:size-full" role="img" aria-label="{{ __('QR code') }}">{!! $qrSvg !!}</div>
                    <p class="text-center"><x-ui.code :value="$secret" /></p>
                @else
                    <p class="text-sm text-ink-muted">{{ __('Enter the code we emailed you.') }}</p>
                @endif
                <x-ui.otp-input wire:model="setupCode" :label="__('Verification code')" x-on:otp-complete="$wire.confirmSetup()" :autofocus="false" />
            </form>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'mfa-setup')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="mfa-setup-form" :loading="$setupStep === 'password' ? 'startSetup' : 'confirmSetup'">
                {{ $setupStep === 'password' ? __('Continue') : __('Turn on') }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-dialog name="mfa-remove" :title="__('Remove this method?')" :confirm-label="__('Remove')" action="removeMethod">
        {{ __('You will no longer be able to use it to sign in.') }}
        <x-slot:fields><div class="mt-4"><x-ui.password :label="__('Your password')" wire:model="confirmPassword" autocomplete="current-password" /></div></x-slot:fields>
    </x-ui.confirm-dialog>

    <x-ui.confirm-dialog name="regenerate-codes" tone="primary" :title="__('Create new recovery codes?')" :confirm-label="__('Create codes')" action="regenerateCodes">
        {{ __('Your old recovery codes will stop working.') }}
        <x-slot:fields><div class="mt-4"><x-ui.password :label="__('Your password')" wire:model="confirmPassword" autocomplete="current-password" /></div></x-slot:fields>
    </x-ui.confirm-dialog>

    <x-ui.confirm-dialog name="sign-out-others" tone="primary" :title="__('Sign out other devices?')" :confirm-label="__('Sign out')" action="revokeOtherSessions">
        {{ __('Every other browser and app will need to sign in again.') }}
    </x-ui.confirm-dialog>

    <x-ui.modal name="recovery-codes" :title="__('Your recovery codes')" :description="__('Save them somewhere safe. Each code works once. They will not be shown again.')">
        <div x-data="{ codes: @js($recoveryCodes) }">
            <ul class="grid grid-cols-2 gap-2 rounded-lg border border-line bg-surface-muted/50 p-4 font-mono text-sm">
                @foreach ($recoveryCodes as $code)<li wire:key="rc-{{ $loop->index }}">{{ $code }}</li>@endforeach
            </ul>
            <div class="mt-3 flex gap-2">
                <x-ui.button size="sm" variant="secondary" icon="download" x-on:click="const url = URL.createObjectURL(new Blob(['CampusOS recovery codes\n\n' + codes.join('\n') + '\n'], { type: 'text/plain' })); const a = document.createElement('a'); a.href = url; a.download = 'campusos-recovery-codes.txt'; a.click(); URL.revokeObjectURL(url)">{{ __('Download') }}</x-ui.button>
                <x-ui.button size="sm" variant="secondary" icon="copy" x-on:click="navigator.clipboard.writeText(codes.join('\n')); $store.toasts.push({ type: 'success', title: @js(__('Copied')) })">{{ __('Copy') }}</x-ui.button>
            </div>
        </div>
        <x-slot:footer><x-ui.button x-on:click="$dispatch('close-dialog', 'recovery-codes')">{{ __('I have saved them') }}</x-ui.button></x-slot:footer>
    </x-ui.modal>
</div>
