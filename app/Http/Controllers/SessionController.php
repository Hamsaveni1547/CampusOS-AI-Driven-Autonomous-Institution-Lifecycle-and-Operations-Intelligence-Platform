<?php

namespace App\Http\Controllers;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use App\Auth\SessionAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Sign-out and institution switching (plain form posts from the top bar).
 */
class SessionController extends Controller
{
    public function __construct(
        private SessionAuth $auth,
        private CampusApi $api,
    ) {}

    public function logout(Request $request): RedirectResponse
    {
        if ($this->auth->check()) {
            try {
                $this->api->auth()->logout();
            } catch (ApiException) {
                // The token may already be invalid; sign out locally regardless.
            }
        }

        $this->auth->forget();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(Route::has('login') ? route('login') : '/')->with('status', __('You have been signed out.'));
    }

    public function switchInstitution(Request $request): RedirectResponse
    {
        $validated = $request->validate(['institution' => ['required', 'string', 'max:40']]);
        $user = $this->auth->user();

        $allowed = $user !== null && ($user->isPlatformAdmin() || collect($user->institutions())->contains('id', $validated['institution']));
        abort_unless($allowed, 403);

        $this->auth->switchInstitution($validated['institution']);
        $this->auth->storeMe($this->api->me()->profile());

        return redirect(Route::has('home') ? route('home') : '/')
            ->with('toast', ['type' => 'success', 'title' => __('Switched to :name', ['name' => $this->auth->user()?->institution()['name'] ?? ''])]);
    }
}
