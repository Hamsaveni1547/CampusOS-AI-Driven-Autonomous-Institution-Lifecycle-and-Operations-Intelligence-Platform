<?php

namespace App\Livewire\Auth;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ValidationFailed;
use App\Auth\DeviceName;
use App\Auth\SessionAuth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Applicant self-registration for an institution (/register/{institution code}).
 * Signs the applicant in afterwards; the next screen asks for the emailed code.
 */
#[Layout('layouts::auth')]
#[Title('Create an applicant account')]
class Register extends Component
{
    #[Locked]
    public string $institution = '';

    public string $name = '';

    public string $email = '';

    public string $mobile = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $acceptTerms = false;

    public ?string $alert = null;

    public bool $closed = false;

    public function mount(string $institution): void
    {
        $this->institution = strtoupper($institution);
    }

    public function register(CampusApi $api, SessionAuth $auth): void
    {
        $this->alert = null;
        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'acceptTerms' => ['accepted'],
        ], ['acceptTerms.accepted' => __('Please accept the privacy notice to continue.'), 'mobile.regex' => __('Enter a 10–15 digit mobile number.')]);

        try {
            $api->auth()->register([
                'institution' => $this->institution,
                'name' => trim($this->name),
                'email' => trim($this->email),
                'mobile' => trim($this->mobile),
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'accept_terms' => true,
            ]);

            $result = $api->auth()->login(trim($this->email), $this->password, DeviceName::fromUserAgent(request()->userAgent()), null);
        } catch (ValidationFailed $exception) {
            foreach ($exception->errors as $field => $messages) {
                $this->addError($field === 'accept_terms' ? 'acceptTerms' : $field, (string) $messages[0]);
            }

            return;
        } catch (ApiException $exception) {
            if ($exception->is('REGISTRATION_CLOSED') || $exception->is('INSTITUTION_NOT_FOUND')) {
                $this->closed = true;
            }

            $this->alert = $exception->getMessage();

            return;
        }

        $auth->signIn($result);
        $this->redirectRoute('required.email');
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
