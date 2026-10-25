<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\RequiredPasswordChange;
use App\Livewire\Auth\RequiredTwoStepSetup;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\SetupAccount;
use App\Livewire\Auth\TwoStepChallenge;
use App\Livewire\Auth\VerifyEmail;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AuthScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    /**
     * @param  array<string, mixed>  $me
     * @return array<string, mixed>
     */
    private function tokenResponse(array $me = []): array
    {
        return ['data' => [
            'mfa_required' => false,
            'token_type' => 'Bearer',
            'access_token' => '9|cos_new',
            'expires_at' => now()->addHours(12)->toIso8601String(),
            'trusted_device_token' => null,
            'user' => $this->me($me),
        ]];
    }

    public function test_pages_render_for_guests(): void
    {
        foreach (['/login', '/forgot-password', '/reset-password?token=abc&email=a@b.co', '/setup-account?token=abc&email=a@b.co', '/register/RVCE'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_signed_in_users_skip_the_sign_in_page(): void
    {
        $this->signIn()->get('/login')->assertRedirect(route('home'));
    }

    public function test_successful_sign_in_stores_token_and_redirects(): void
    {
        Http::fake([self::API.'auth/login' => Http::response($this->tokenResponse())]);

        Livewire::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64) Chrome/130.0'])
            ->test(Login::class)
            ->set('login', 'anita@example.edu')
            ->set('password', 'Secret@123')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertSame('9|cos_new', session('campus.token'));
        $this->assertSame('Anita Rao', session('campus.me.name'));

        Http::assertSent(fn (Request $request) => is_string($request['device_name']) && $request['device_name'] !== '' && $request['login'] === 'anita@example.edu');
    }

    public function test_wrong_details_and_locked_account_show_alerts(): void
    {
        Http::fake([self::API.'auth/login' => Http::sequence()
            ->push($this->apiError('The sign-in details are incorrect.', 'INVALID_CREDENTIALS'), 422)
            ->push($this->apiError('Locked', 'ACCOUNT_LOCKED', ['context' => ['retry_after_minutes' => 12]]), 423)]);

        $component = Livewire::test(Login::class)->set('login', 'anita@example.edu')->set('password', 'bad');

        $component->call('authenticate')
            ->assertSet('alert', 'The email/username or password is incorrect.')
            ->assertSet('password', '')
            ->assertNoRedirect();

        $component->set('password', 'bad')->call('authenticate')
            ->assertSet('alertTone', 'warning')
            ->assertSee('12 minutes');

        $this->assertNull(session('campus.token'));
    }

    public function test_required_fields_are_validated_before_calling_the_api(): void
    {
        Livewire::test(Login::class)->call('authenticate')->assertHasErrors(['login', 'password']);

        Http::assertNothingSent();
    }

    public function test_mfa_flow_with_authenticator_and_remember_device(): void
    {
        Http::fake([
            self::API.'auth/login' => Http::response(['data' => [
                'mfa_required' => true,
                'challenge_token' => 'challenge-abc',
                'challenge_expires_at' => now()->addMinutes(10)->toIso8601String(),
                'methods' => ['totp', 'email'],
            ]]),
            self::API.'auth/mfa/verify' => Http::sequence()
                ->push($this->apiError('Wrong', 'MFA_INVALID_CODE', ['context' => ['attempts_remaining' => 4]]), 422)
                ->push(['data' => array_merge($this->tokenResponse()['data'], ['trusted_device_token' => 'trusted-xyz'])]),
        ]);

        Livewire::test(Login::class)->set('login', 'anita@example.edu')->set('password', 'Secret@123')->call('authenticate')
            ->assertRedirect(route('login.verify'));

        $this->assertSame('challenge-abc', session('campus.mfa_challenge.token'));
        $this->assertNull(session('campus.token'));

        $challenge = Livewire::test(TwoStepChallenge::class)
            ->assertSet('method', 'totp')
            ->assertSet('methods', ['totp', 'email']);

        $challenge->set('code', '000000')->call('verify')
            ->assertHasErrors('code')
            ->assertSee('4 attempt(s) left');

        $challenge->set('code', '123456')->set('rememberDevice', true)->call('verify')
            ->assertRedirect(route('home'));

        $this->assertSame('9|cos_new', session('campus.token'));
        $this->assertNull(session('campus.mfa_challenge'));

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'auth/mfa/verify') && $request['remember_device'] === true && $request['method'] === 'totp');
    }

    public function test_email_code_can_be_sent_and_recovery_code_used(): void
    {
        Http::fake([
            self::API.'auth/mfa/send-code' => Http::response(['data' => ['sent_to' => 'an***@example.edu', 'expires_at' => now()->addMinutes(5)->toIso8601String()]]),
            self::API.'auth/mfa/verify' => Http::response($this->tokenResponse()),
        ]);

        $this->withSession(['campus.mfa_challenge' => ['token' => 'ch', 'expires_at' => now()->addMinutes(5)->toIso8601String(), 'methods' => ['email'], 'login' => 'anita@example.edu']]);

        Livewire::test(TwoStepChallenge::class)
            ->assertSet('method', 'email')
            ->call('sendEmailCode')
            ->assertSet('sentTo', 'an***@example.edu')
            ->assertDispatched('toast')
            ->call('useMethod', 'recovery_code')
            ->set('code', 'ABCDE-12345')
            ->call('verify')
            ->assertRedirect(route('home'));

        Http::assertSent(fn (Request $request) => ($request['method'] ?? null) === 'recovery_code');
    }

    public function test_expired_challenge_returns_to_sign_in(): void
    {
        Livewire::test(TwoStepChallenge::class)->assertRedirect(route('login'));

        Http::fake([self::API.'auth/mfa/verify' => Http::response($this->apiError('Expired', 'MFA_CHALLENGE_EXHAUSTED'), 422)]);
        $this->withSession(['campus.mfa_challenge' => ['token' => 'ch', 'expires_at' => null, 'methods' => ['totp'], 'login' => 'a']]);

        Livewire::test(TwoStepChallenge::class)->set('code', '111111')->call('verify')->assertRedirect(route('login'));
        $this->assertNull(session('campus.mfa_challenge'));
    }

    public function test_forgot_password_always_confirms(): void
    {
        Http::fake([self::API.'auth/forgot-password' => Http::response(['message' => 'ok'])]);

        Livewire::test(ForgotPassword::class)
            ->call('send')->assertHasErrors('email')
            ->set('email', 'someone@example.edu')->call('send')
            ->assertSet('sent', true)
            ->assertSee('Check your email');
    }

    public function test_reset_password_and_invalid_link(): void
    {
        Http::fake([self::API.'auth/reset-password' => Http::sequence()
            ->push($this->apiError('Invalid', 'VALIDATION_FAILED', ['errors' => ['password' => ['You cannot reuse any of your last 5 passwords.']]]), 422)
            ->push(['message' => 'ok'])
            ->push($this->apiError('Expired', 'INVALID_RESET_TOKEN'), 422)]);

        $this->get('/reset-password?token=tok&email=anita@example.edu')->assertOk();

        $component = Livewire::withQueryParams(['token' => 'tok', 'email' => 'anita@example.edu'])->test(ResetPassword::class)
            ->set('password', 'New@Password1')->set('password_confirmation', 'New@Password1');

        $component->call('save')->assertHasErrors(['password'])->assertSee('last 5 passwords');
        $component->call('save')->assertRedirect(route('login'));

        Livewire::withQueryParams(['token' => 'tok', 'email' => 'anita@example.edu'])->test(ResetPassword::class)
            ->set('password', 'New@Password1')->set('password_confirmation', 'New@Password1')
            ->call('save')
            ->assertSet('linkInvalid', true)
            ->assertSee('This link has expired');

        Livewire::withQueryParams([])->test(ResetPassword::class)->assertSet('linkInvalid', true);
    }

    public function test_setup_account_uses_the_invitation_endpoint(): void
    {
        Http::fake([self::API.'auth/setup-account' => Http::response(['message' => 'ok'])]);

        Livewire::withQueryParams(['token' => 'inv', 'email' => 'new@example.edu'])->test(SetupAccount::class)
            ->assertSee('Welcome to CampusOS')
            ->set('password', 'First@Pass123')->set('password_confirmation', 'First@Pass123')
            ->call('save')
            ->assertRedirect(route('login'));

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'auth/setup-account'));
    }

    public function test_applicant_registration_signs_in_and_asks_for_email_code(): void
    {
        Http::fake([
            self::API.'auth/register' => Http::response(['message' => 'ok'], 201),
            self::API.'auth/login' => Http::response($this->tokenResponse(['status' => 'pending_verification', 'pending_requirement' => 'EMAIL_VERIFICATION_REQUIRED', 'roles' => ['applicant']])),
        ]);

        Livewire::test(Register::class, ['institution' => 'rvce'])
            ->assertSet('institution', 'RVCE')
            ->call('register')
            ->assertHasErrors(['name', 'email', 'mobile', 'password', 'acceptTerms'])
            ->set('name', 'Asha Kumar')->set('email', 'asha@example.com')->set('mobile', '9876501234')
            ->set('password', 'Strong@Pass1')->set('password_confirmation', 'Strong@Pass1')->set('acceptTerms', true)
            ->call('register')
            ->assertRedirect(route('required.email'));

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'auth/register') && $request['institution'] === 'RVCE');
    }

    public function test_closed_registration_is_explained(): void
    {
        Http::fake([self::API.'auth/register' => Http::response($this->apiError('Online registration is not open for this institution.', 'REGISTRATION_CLOSED'), 403)]);

        Livewire::test(Register::class, ['institution' => 'RVCE'])
            ->set('name', 'A')->set('email', 'a@example.com')->set('mobile', '9876501234')
            ->set('password', 'Strong@Pass1')->set('password_confirmation', 'Strong@Pass1')->set('acceptTerms', true)
            ->call('register')
            ->assertSet('closed', true)
            ->assertSee('Registration is not open');
    }

    public function test_email_verification_step(): void
    {
        Http::fake([self::API.'auth/verify-email' => Http::sequence()
            ->push($this->apiError('Wrong', 'INVALID_CODE'), 422)
            ->push(['data' => $this->me(['status' => 'active'])])]);

        $this->signIn(['pending_requirement' => 'EMAIL_VERIFICATION_REQUIRED']);
        $this->get('/')->assertRedirect(route('required.email'));

        $component = Livewire::test(VerifyEmail::class)->set('code', '12')->call('verify')->assertHasErrors('code');
        $component->set('code', '000000')->call('verify')->assertHasErrors('code')->assertSee('not right');
        $component->set('code', '123456')->call('verify')->assertRedirect(route('home'));

        $this->assertNull(session('campus.me.pending_requirement'));
    }

    public function test_required_password_change(): void
    {
        Http::fake([
            self::API.'me/password' => Http::response(['message' => 'ok']),
            self::API.'me' => Http::response(['data' => $this->me()]),
        ]);

        $this->signIn(['pending_requirement' => 'PASSWORD_CHANGE_REQUIRED']);

        Livewire::test(RequiredPasswordChange::class)
            ->set('current_password', 'Temp@1234')->set('password', 'Temp@1234')->set('password_confirmation', 'Temp@1234')
            ->call('save')->assertHasErrors('password')
            ->set('password', 'Better@Pass9')->set('password_confirmation', 'Better@Pass9')
            ->call('save')
            ->assertRedirect(route('home'));
    }

    public function test_required_two_step_setup_with_authenticator(): void
    {
        Http::fake([
            self::API.'me/mfa/totp' => Http::response(['data' => ['secret' => 'JBSWY3DPEHPK3PXP', 'otpauth_uri' => 'otpauth://totp/x', 'qr_svg' => '<svg viewBox="0 0 10 10"><rect width="10" height="10"/></svg>']]),
            self::API.'me/mfa/totp/confirm' => Http::response(['data' => ['method' => ['id' => 'm1', 'type' => 'totp'], 'recovery_codes' => ['AAAAA-11111', 'BBBBB-22222']]]),
            self::API.'me' => Http::response(['data' => $this->me()]),
        ]);

        $this->signIn(['pending_requirement' => 'MFA_ENROLLMENT_REQUIRED', 'roles' => ['institution_admin']]);

        Livewire::test(RequiredTwoStepSetup::class)
            ->call('choose', 'totp')->assertSet('step', 'password')
            ->call('start')->assertHasErrors('current_password')
            ->set('current_password', 'Secret@123')->call('start')
            ->assertSet('step', 'code')
            ->assertSee('JBSWY3DPEHPK3PXP')
            ->set('code', '123456')->call('confirm')
            ->assertSet('step', 'recovery')
            ->assertSee('AAAAA-11111')
            ->call('finish')->assertHasErrors('savedCodes')
            ->set('savedCodes', true)->call('finish')
            ->assertRedirect(route('home'));
    }

    public function test_required_screens_leave_when_nothing_is_pending(): void
    {
        $this->signIn();

        Livewire::test(VerifyEmail::class)->assertRedirect(route('home'));
        Livewire::test(RequiredPasswordChange::class)->assertRedirect(route('home'));
        Livewire::test(RequiredTwoStepSetup::class)->assertRedirect(route('home'));
    }
}
