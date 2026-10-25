<?php

namespace App\Livewire\Account;

use App\Auth\SessionAuth;
use App\Auth\TrustedDevice;
use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Password, two-step verification, signed-in devices and sign-in history.
 */
#[Layout('layouts::app')]
#[Title('Security')]
class Security extends Component
{
    use InteractsWithApi;

    // Change password
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Two-step setup (dialog)
    public string $setupMethod = 'totp';

    public string $setupStep = 'password';

    public string $setupPassword = '';

    public string $setupCode = '';

    #[Locked]
    public ?string $qrSvg = null;

    #[Locked]
    public ?string $secret = null;

    /**
     * Shown once after setup or regeneration.
     *
     * @var array<int, string>
     */
    #[Locked]
    public array $recoveryCodes = [];

    // Removing a method / regenerating codes needs the password
    public string $confirmPassword = '';

    public ?string $pendingRemoval = null;

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', 'different:current_password'],
        ]);

        $this->callApi(
            fn () => $this->api()->me()->changePassword($this->current_password, $this->password, $this->password_confirmation),
            fieldMap: ['current_password' => 'current_password', 'password' => 'password'],
            onSuccess: function (): void {
                $this->reset('current_password', 'password', 'password_confirmation');
                $this->toast('success', __('Password changed'), __('You were signed out of other devices.'));
            },
        );
    }

    public function openSetup(string $method): void
    {
        $this->setupMethod = in_array($method, ['totp', 'email'], true) ? $method : 'totp';
        $this->reset('setupStep', 'setupPassword', 'setupCode', 'qrSvg', 'secret');
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'mfa-setup');
    }

    public function startSetup(): void
    {
        $this->validate(['setupPassword' => ['required', 'string']]);

        $this->callApi(function (): bool {
            if ($this->setupMethod === 'totp') {
                $setup = $this->api()->me()->startTotp($this->setupPassword);
                $this->qrSvg = $setup['qr_svg'];
                $this->secret = $setup['secret'];
            } else {
                $this->api()->me()->startEmailMfa($this->setupPassword);
            }

            return true;
        }, fieldMap: ['current_password' => 'setupPassword'], onSuccess: function (): void {
            $this->setupPassword = '';
            $this->setupStep = 'code';
        }, toastErrors: false);

        if ($this->formError !== null) {
            $this->addError('setupPassword', $this->formError);
        }
    }

    public function confirmSetup(): void
    {
        if (preg_match('/^\d{6}$/', trim($this->setupCode)) !== 1) {
            $this->addError('setupCode', __('Enter the 6-digit code.'));

            return;
        }

        $result = $this->callApi(fn () => $this->setupMethod === 'totp'
            ? $this->api()->me()->confirmTotp(trim($this->setupCode))
            : $this->api()->me()->confirmEmailMfa(trim($this->setupCode)), toastErrors: false);

        if ($result === null) {
            $this->setupCode = '';
            $this->addError('setupCode', $this->formError ?? __('That code is not right.'));

            return;
        }

        $this->recoveryCodes = $result['recovery_codes'] ?? [];
        $this->dispatch('close-dialog', 'mfa-setup');
        $this->refreshMe();
        $this->toast('success', __('Two-step verification method added'));

        if ($this->recoveryCodes !== []) {
            $this->dispatch('open-dialog', 'recovery-codes');
        }
    }

    public function askRemove(string $methodId): void
    {
        $this->pendingRemoval = $methodId;
        $this->confirmPassword = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'mfa-remove');
    }

    public function removeMethod(): void
    {
        if ($this->pendingRemoval === null) {
            return;
        }

        $this->validate(['confirmPassword' => ['required', 'string']]);

        $this->callApi(fn () => $this->api()->me()->removeMfaMethod($this->pendingRemoval, $this->confirmPassword),
            fieldMap: ['current_password' => 'confirmPassword'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'mfa-remove');
                $this->reset('pendingRemoval', 'confirmPassword');
                $this->refreshMe();
                $this->toast('success', __('Method removed'));
            });
    }

    public function makePrimary(string $methodId): void
    {
        $this->callApi(fn () => $this->api()->me()->makeMfaPrimary($methodId), onSuccess: fn () => $this->toast('success', __('Default method updated')));
    }

    public function regenerateCodes(): void
    {
        $this->validate(['confirmPassword' => ['required', 'string']]);

        $codes = $this->callApi(fn () => $this->api()->me()->regenerateRecoveryCodes($this->confirmPassword), fieldMap: ['current_password' => 'confirmPassword']);

        if ($codes !== null) {
            $this->recoveryCodes = $codes;
            $this->confirmPassword = '';
            $this->dispatch('close-dialog', 'regenerate-codes');
            $this->dispatch('open-dialog', 'recovery-codes');
        }
    }

    public function revokeSession(string $id): void
    {
        $this->callApi(fn () => $this->api()->me()->revokeSession($id), onSuccess: fn () => $this->toast('success', __('Device signed out')));
    }

    public function revokeOtherSessions(): void
    {
        $this->callApi(fn () => $this->api()->me()->revokeOtherSessions(), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'sign-out-others');
            $this->toast('success', __('Signed out of all other devices'));
        });
    }

    public function forgetDevice(string $id): void
    {
        $this->callApi(fn () => $this->api()->me()->revokeTrustedDevice($id), onSuccess: function (): void {
            TrustedDevice::forget();
            $this->toast('success', __('The device will ask for verification again'));
        });
    }

    public function render(): View
    {
        $me = $this->api()->me();

        return view('livewire.account.security', [
            'mfa' => $me->mfa(),
            'sessions' => $me->sessions(),
            'devices' => $me->trustedDevices(),
            'events' => array_slice($me->securityEvents()->items, 0, 10),
        ]);
    }

    private function refreshMe(): void
    {
        app(SessionAuth::class)->storeMe($this->api()->me()->profile());
    }
}
