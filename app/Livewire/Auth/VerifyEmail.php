<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Auth\SessionAuth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Required step: confirm the email address with the 6-digit code.
 */
#[Layout('layouts::auth')]
#[Title('Verify your email')]
class VerifyEmail extends Component
{
    public string $code = '';

    public int $resendAvailableAt = 0;

    public function mount(SessionAuth $auth): void
    {
        if ($auth->user()?->pendingRequirement() !== 'EMAIL_VERIFICATION_REQUIRED') {
            $this->redirect(Route::has('home') ? route('home') : '/');
        }

        $this->resendAvailableAt = now()->addSeconds(60)->getTimestamp();
    }

    public function verify(CampusApi $api, SessionAuth $auth): void
    {
        $this->resetErrorBag();

        if (preg_match('/^\d{6}$/', trim($this->code)) !== 1) {
            $this->addError('code', __('Enter the 6-digit code.'));

            return;
        }

        try {
            $me = $api->auth()->verifyEmail(trim($this->code));
        } catch (ApiException $exception) {
            $this->code = '';
            $this->addError('code', $exception->is('INVALID_CODE') ? __('That code is not right or has expired.') : $exception->getMessage());

            return;
        }

        $auth->storeMe($me);
        session()->flash('toast', ['type' => 'success', 'title' => __('Email verified')]);
        $this->redirect(Route::has('home') ? route('home') : '/');
    }

    public function resend(CampusApi $api): void
    {
        try {
            $api->auth()->resendEmailVerification();
            $this->resendAvailableAt = now()->addSeconds(60)->getTimestamp();
            $this->dispatch('toast', type: 'success', title: __('A new code is on its way.'));
        } catch (ApiException $exception) {
            $this->resendAvailableAt = now()->addSeconds((int) ($exception->context['retry_after_seconds'] ?? 30))->getTimestamp();
            $this->addError('code', $exception->getMessage());
        }
    }

    public function render(SessionAuth $auth): View
    {
        return view('livewire.auth.verify-email', ['email' => $auth->user()?->email()]);
    }
}
