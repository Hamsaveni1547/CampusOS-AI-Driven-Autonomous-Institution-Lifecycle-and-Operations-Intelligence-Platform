<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ValidationFailed;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Set a new password from the emailed reset link (/reset-password?token=…&email=…).
 * SetupAccount reuses this for invitation links.
 */
#[Layout('layouts::auth')]
#[Title('Set a new password')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    #[Locked]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $linkInvalid = false;

    public ?string $alert = null;

    protected bool $isAccountSetup = false;

    public function mount(): void
    {
        $this->token = (string) request()->query('token', '');
        $this->email = (string) request()->query('email', '');
        $this->linkInvalid = $this->token === '' || filter_var($this->email, FILTER_VALIDATE_EMAIL) === false;
    }

    public function save(CampusApi $api): void
    {
        $this->alert = null;
        $this->resetErrorBag();

        $this->validate([
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ]);

        try {
            $api->auth()->resetPassword($this->email, $this->token, $this->password, $this->password_confirmation, $this->isAccountSetup);
        } catch (ValidationFailed $exception) {
            foreach ($exception->errors as $field => $messages) {
                $this->addError('password', (string) $messages[0]);
            }

            return;
        } catch (ApiException $exception) {
            if ($exception->is('INVALID_RESET_TOKEN')) {
                $this->linkInvalid = true;

                return;
            }

            $this->alert = $exception->getMessage();

            return;
        }

        session()->flash('status', $this->isAccountSetup
            ? __('Your account is ready. Sign in with your new password.')
            : __('Your password has been changed. Sign in with your new password.'));

        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password', ['isAccountSetup' => $this->isAccountSetup]);
    }
}
