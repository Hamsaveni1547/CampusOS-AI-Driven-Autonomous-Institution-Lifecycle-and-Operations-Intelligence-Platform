<?php

use App\Http\Controllers\SessionController;
use App\Livewire\Account\NotificationPreferences;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Security;
use App\Livewire\Account\StaffProfile;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\RequiredPasswordChange;
use App\Livewire\Auth\RequiredTwoStepSetup;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\SetupAccount;
use App\Livewire\Auth\TwoStepChallenge;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Calendar;
use App\Livewire\Home;
use App\Livewire\Notifications;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CampusOS web routes
|--------------------------------------------------------------------------
| Guests: sign-in and recovery. Signed in: required account steps, then
| everything else behind campus.ready (no pending requirement).
*/

Route::middleware('campus.guest')->group(function (): void {
    Route::livewire('login', Login::class)->name('login');
    Route::livewire('login/verify', TwoStepChallenge::class)->name('login.verify');
    Route::livewire('forgot-password', ForgotPassword::class)->name('password.forgot');
    Route::livewire('reset-password', ResetPassword::class)->name('password.reset');
    Route::livewire('setup-account', SetupAccount::class)->name('account.setup');
    Route::livewire('register/{institution}', Register::class)->where('institution', '[A-Za-z0-9_-]{2,32}')->name('register');
});

Route::middleware(['campus.auth'])->group(function (): void {
    Route::post('logout', [SessionController::class, 'logout'])->name('logout');
    Route::post('institution/switch', [SessionController::class, 'switchInstitution'])->name('institution.switch');

    // Steps that must be completed before using the app (see EnsureAccountReady).
    Route::livewire('required/email', VerifyEmail::class)->name('required.email');
    Route::livewire('required/password', RequiredPasswordChange::class)->name('required.password');
    Route::livewire('required/two-step', RequiredTwoStepSetup::class)->name('required.two-step');

    Route::middleware('campus.ready')->group(function (): void {
        Route::livewire('/', Home::class)->name('home');
        Route::livewire('notifications', Notifications::class)->name('notifications.index');
        Route::livewire('calendar', Calendar::class)->name('calendar.index');

        Route::prefix('account')->name('account.')->group(function (): void {
            Route::livewire('/', Profile::class)->name('profile');
            Route::livewire('security', Security::class)->name('security');
            Route::livewire('notifications', NotificationPreferences::class)->name('notifications');
            Route::livewire('staff-record', StaffProfile::class)->name('staff-profile');
        });
    });
});

// Local component catalogue (never in production).
if (config('campusos.style_guide')) {
    Route::view('_ui', 'pages.style-guide')->name('style-guide');
}
