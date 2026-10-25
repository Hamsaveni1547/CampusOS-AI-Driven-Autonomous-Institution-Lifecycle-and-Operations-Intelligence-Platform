<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ValidationFailed;
use App\Auth\DeviceName;
use App\Auth\SessionAuth;
use App\Auth\TrustedDevice;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Sign in with email, username or mobile + password. Hands over to the two-step
 * screen when the account uses it.
 */
#[Layout('layouts::auth')]
#[Title('Sign in')]
class Login extends Component
{
    #[Validate('required|string|max:255', as: 'email, username or mobile')]
    public string $login = '';

    #[Validate('required|string|max:255')]
    public string $password = '';

    /**
     * Problem shown above the form (wrong details, locked, deactivated…).
     */
    public ?string $alert = null;

    public string $alertTone = 'danger';

    public function mount(): void
    {
        $status = session('status');

        if (is_string($status)) {
            $this->alert = $status;
            $this->alertTone = 'info';
        }
    }

    public function authenticate(CampusApi $api, SessionAuth $auth): void
    {
        $this->alert = null;
        $this->validate();

        $throttleKey = 'web-login:'.mb_strtolower(trim($this->login)).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->showAlert(__('Too many attempts. Try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($throttleKey)]));

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        try {
            $result = $api->auth()->login(
                trim($this->login),
                $this->password,
                DeviceName::fromUserAgent(request()->userAgent()),
                TrustedDevice::token(),
            );
        } catch (ValidationFailed $exception) {
            foreach ($exception->errors as $field => $messages) {
                $this->addError(in_array($field, ['login', 'password'], true) ? $field : 'login', (string) $messages[0]);
            }

            return;
        } catch (ApiException $exception) {
            $this->password = '';
            $this->showAlert($this->messageFor($exception), $exception->is('ACCOUNT_LOCKED') ? 'warning' : 'danger');

            return;
        }

        RateLimiter::clear($throttleKey);

        if ($result['mfa_required'] ?? false) {
            $auth->storeChallenge($result, trim($this->login));
            $this->redirectRoute('login.verify', navigate: false);

            return;
        }

        $auth->signIn($result);
        $this->redirectIntended(Route::has('home') ? route('home') : '/');
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }

    private function messageFor(ApiException $exception): string
    {
        return match ($exception->errorCode) {
            'INVALID_CREDENTIALS' => __('The email/username or password is incorrect.'),
            'ACCOUNT_LOCKED' => __('This account is locked for :minutes minutes after too many failed attempts. You can wait, or reset your password.', ['minutes' => $exception->context['retry_after_minutes'] ?? 15]),
            'ACCOUNT_NOT_SET_UP' => __('Your account is not set up yet. Use the link in your invitation email to set a password.'),
            'API_UNREACHABLE' => __('CampusOS is not reachable right now. Please try again in a moment.'),
            default => $exception->getMessage(),
        };
    }

    private function showAlert(string $message, string $tone = 'danger'): void
    {
        $this->alert = $message;
        $this->alertTone = $tone;
    }
}
