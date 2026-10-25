<?php

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CampusOS web routes
|--------------------------------------------------------------------------
| Guests: sign-in pages (F2).  Signed in: everything else, behind
| campus.auth (token present) and campus.ready (no pending requirement).
*/

Route::middleware(['campus.auth'])->group(function (): void {
    Route::post('logout', [SessionController::class, 'logout'])->name('logout');
    Route::post('institution/switch', [SessionController::class, 'switchInstitution'])->name('institution.switch');

    Route::middleware('campus.ready')->group(function (): void {
        Route::view('/', 'pages.home')->name('home');
    });
});

// Local component catalogue (never in production).
if (config('campusos.style_guide')) {
    Route::view('_ui', 'pages.style-guide')->name('style-guide');
}
