<?php

use App\Http\Controllers\SessionController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\RequiredPasswordChange;
use App\Livewire\Auth\RequiredTwoStepSetup;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\SetupAccount;
use App\Livewire\Auth\TwoStepChallenge;
use App\Livewire\Auth\VerifyEmail;
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
        Route::view('/', 'pages.home')->name('home');
    });
});

// Local component catalogue (never in production).
if (config('campusos.style_guide')) {
    Route::view('_ui', 'pages.style-guide')->name('style-guide');
}
