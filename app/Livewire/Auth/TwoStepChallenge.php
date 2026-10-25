<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\RateLimited;
use App\Auth\SessionAuth;
use App\Auth\TrustedDevice;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Second sign-in step: authenticator app code, email code or a recovery code.
 */
#[Layout('layouts::auth')]
#[Title('Two-step verification')]
class TwoStepChallenge extends Component
{
    /**
     * totp | email | recovery_code
     */
    public string $method = 'totp';

    public string $code = '';

    public bool $rememberDevice = false;

    /**
     * @var array<int, string>
     */
    #[Locked]
    public array $methods = [];

    #[Locked]
    public string $login = '';

    public ?string $sentTo = null;

    /**
     * Unix time when another email code may be requested.
     */
    public int $resendAvailableAt = 0;

    public function mount(SessionAuth $auth): void
    {
        $challenge = $auth->challenge();

        if ($challenge === null) {
            session()->flash('status', __('Your sign-in step expired. Please sign in again.'));
            $this->redirectRoute('login');

            return;
        }

        $this->methods = $challenge['methods'];
        $this->login = $challenge['login'];
        $this->method = in_array('totp', $this->methods, true) ? 'totp' : ($this->methods[0] ?? 'recovery_code');
    }

    public function useMethod(string $method): void
    {
        if (! in_array($method, [...$this->methods, 'recovery_code'], true)) {
            return;
        }

        $this->method = $method;
        $this->code = '';
        $this->resetErrorBag();
    }

    public function sendEmailCode(CampusApi $api, SessionAuth $auth): void
    {
        $challenge = $auth->challenge();

        if ($challenge === null) {
            $this->expired();

            return;
        }

        try {
            $sent = $api->auth()->sendMfaCode($challenge['token'], 'email');
            $this->sentTo = $sent['sent_to'];
            $this->resendAvailableAt = now()->addSeconds(60)->getTimestamp();
            $this->dispatch('toast', type: 'success', title: __('Code sent to :to', ['to' => $this->sentTo]));
        } catch (RateLimited $exception) {
            $this->resendAvailableAt = now()->addSeconds((int) ($exception->context['retry_after_seconds'] ?? 60))->getTimestamp();
            $this->addError('code', $exception->getMessage());
        } catch (ApiException $exception) {
            $this->handleChallengeError($exception);
        }
    }

    public function verify(CampusApi $api, SessionAuth $auth): void
    {
        $this->resetErrorBag();
        $code = trim($this->code);

        if ($code === '') {
            $this->addError('code', $this->method === 'recovery_code' ? __('Enter one of your recovery codes.') : __('Enter the 6-digit code.'));

            return;
        }

        $challenge = $auth->challenge();

        if ($challenge === null) {
            $this->expired();

            return;
        }

        try {
            $result = $api->auth()->verifyMfa($challenge['token'], $this->method, $code, $this->rememberDevice);
        } catch (ApiException $exception) {
            $this->code = '';
            $this->handleChallengeError($exception);

            return;
        }

        if (is_string($result['trusted_device_token'] ?? null)) {
            TrustedDevice::remember($result['trusted_device_token']);
        }

        $auth->signIn($result);
        $this->redirectIntended(Route::has('home') ? route('home') : '/');
    }

    public function cancel(SessionAuth $auth): void
    {
        $auth->forgetChallenge();
        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.auth.two-step-challenge');
    }

    private function handleChallengeError(ApiException $exception): void
    {
        if (in_array($exception->errorCode, ['MFA_CHALLENGE_EXPIRED', 'MFA_CHALLENGE_EXHAUSTED'], true)) {
            $this->expired($exception->getMessage());

            return;
        }

        $remaining = $exception->context['attempts_remaining'] ?? null;

        $this->addError('code', $remaining !== null
            ? __('That code is not right. :count attempt(s) left.', ['count' => $remaining])
            : $exception->getMessage());
    }

    private function expired(?string $message = null): void
    {
        app(SessionAuth::class)->forgetChallenge();
        session()->flash('status', $message ?? __('Your sign-in step expired. Please sign in again.'));
        $this->redirectRoute('login');
    }
}
