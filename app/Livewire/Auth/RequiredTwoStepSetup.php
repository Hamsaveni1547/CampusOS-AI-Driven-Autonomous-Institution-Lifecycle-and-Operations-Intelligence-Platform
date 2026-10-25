<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ValidationFailed;
use App\Auth\SessionAuth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Required step for roles that must use two-step verification:
 * choose authenticator app or email → confirm password → enter code → save recovery codes.
 */
#[Layout('layouts::auth')]
#[Title('Set up two-step verification')]
class RequiredTwoStepSetup extends Component
{
    /**
     * choose | password | code | recovery
     */
    public string $step = 'choose';

    /**
     * totp | email
     */
    public string $method = 'totp';

    public string $current_password = '';

    public string $code = '';

    #[Locked]
    public ?string $qrSvg = null;

    #[Locked]
    public ?string $secret = null;

    #[Locked]
    public ?string $sentTo = null;

    /**
     * @var array<int, string>
     */
    #[Locked]
    public array $recoveryCodes = [];

    public bool $savedCodes = false;

    public function mount(SessionAuth $auth): void
    {
        if ($auth->user()?->pendingRequirement() !== 'MFA_ENROLLMENT_REQUIRED') {
            $this->redirect(Route::has('home') ? route('home') : '/');
        }
    }

    public function choose(string $method): void
    {
        $this->method = in_array($method, ['totp', 'email'], true) ? $method : 'totp';
        $this->step = 'password';
        $this->resetErrorBag();
    }

    public function back(): void
    {
        $this->step = 'choose';
        $this->current_password = '';
        $this->code = '';
        $this->resetErrorBag();
    }

    public function start(CampusApi $api): void
    {
        $this->resetErrorBag();
        $this->validate(['current_password' => ['required', 'string']]);

        try {
            if ($this->method === 'totp') {
                $setup = $api->me()->startTotp($this->current_password);
                $this->qrSvg = $setup['qr_svg'];
                $this->secret = $setup['secret'];
            } else {
                $this->sentTo = $api->me()->startEmailMfa($this->current_password);
            }
        } catch (ValidationFailed $exception) {
            $this->addError('current_password', (string) ($exception->errors['current_password'][0] ?? $exception->getMessage()));

            return;
        } catch (ApiException $exception) {
            $this->addError('current_password', $exception->getMessage());

            return;
        }

        $this->current_password = '';
        $this->step = 'code';
    }

    public function confirm(CampusApi $api): void
    {
        $this->resetErrorBag();

        if (preg_match('/^\d{6}$/', trim($this->code)) !== 1) {
            $this->addError('code', __('Enter the 6-digit code.'));

            return;
        }

        try {
            $result = $this->method === 'totp'
                ? $api->me()->confirmTotp(trim($this->code))
                : $api->me()->confirmEmailMfa(trim($this->code));
        } catch (ApiException $exception) {
            $this->code = '';
            $this->addError('code', $exception->is('INVALID_CODE')
                ? ($this->method === 'totp' ? __('That code is not right. Check that the time on your phone is correct and try again.') : __('That code is not right or has expired.'))
                : $exception->getMessage());

            return;
        }

        $this->recoveryCodes = $result['recovery_codes'] ?? [];
        $this->qrSvg = null;
        $this->secret = null;
        $this->step = 'recovery';
    }

    public function finish(CampusApi $api, SessionAuth $auth): void
    {
        if ($this->recoveryCodes !== [] && ! $this->savedCodes) {
            $this->addError('savedCodes', __('Please confirm you saved your recovery codes.'));

            return;
        }

        $auth->storeMe($api->me()->profile());
        session()->flash('toast', ['type' => 'success', 'title' => __('Two-step verification is on')]);
        $this->redirectIntended(Route::has('home') ? route('home') : '/');
    }

    public function render(): View
    {
        return view('livewire.auth.required-two-step-setup');
    }
}
