<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ValidationFailed;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Request a password reset link. Always shows the same confirmation so it does not
 * reveal which emails have accounts.
 */
#[Layout('layouts::auth')]
#[Title('Forgot password')]
class ForgotPassword extends Component
{
    #[Validate('required|email|max:255')]
    public string $email = '';

    public bool $sent = false;

    public ?string $alert = null;

    public function send(CampusApi $api): void
    {
        $this->alert = null;
        $this->validate();

        try {
            $api->auth()->forgotPassword(trim($this->email));
        } catch (ValidationFailed $exception) {
            $this->addError('email', (string) ($exception->errors['email'][0] ?? $exception->getMessage()));

            return;
        } catch (ApiException $exception) {
            if (! $exception->is('RESET_THROTTLED')) {
                $this->alert = $exception->getMessage();

                return;
            }
        }

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
