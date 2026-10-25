<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ValidationFailed;
use App\Auth\SessionAuth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Required step: set a new password (temporary password from an admin, or expired password).
 */
#[Layout('layouts::auth')]
#[Title('Change your password')]
class RequiredPasswordChange extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(SessionAuth $auth): void
    {
        if ($auth->user()?->pendingRequirement() !== 'PASSWORD_CHANGE_REQUIRED') {
            $this->redirect(Route::has('home') ? route('home') : '/');
        }
    }

    public function save(CampusApi $api, SessionAuth $auth): void
    {
        $this->resetErrorBag();
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', 'different:current_password'],
        ], ['password.different' => __('Choose a password different from the current one.')]);

        try {
            $api->me()->changePassword($this->current_password, $this->password, $this->password_confirmation);
            $auth->storeMe($api->me()->profile());
        } catch (ValidationFailed $exception) {
            foreach ($exception->errors as $field => $messages) {
                $this->addError(in_array($field, ['current_password', 'password'], true) ? $field : 'password', (string) $messages[0]);
            }

            return;
        } catch (ApiException $exception) {
            $this->addError('password', $exception->getMessage());

            return;
        }

        session()->flash('toast', ['type' => 'success', 'title' => __('Password changed'), 'message' => __('You were signed out of other devices.')]);
        $this->redirect($this->next($auth));
    }

    public function render(): View
    {
        return view('livewire.auth.required-password-change');
    }

    private function next(SessionAuth $auth): string
    {
        // Another requirement (e.g. two-step setup) may follow.
        return $auth->user()?->pendingRequirement() === 'MFA_ENROLLMENT_REQUIRED'
            ? route('required.two-step')
            : (Route::has('home') ? route('home') : '/');
    }
}
