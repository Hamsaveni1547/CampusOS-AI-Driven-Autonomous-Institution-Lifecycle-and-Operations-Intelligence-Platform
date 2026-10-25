<?php

namespace App\Http\Middleware;

use App\Api\CampusApi;
use App\Auth\SessionAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages for signed-in users. Refreshes the `/me` snapshot when it is older than
 * campusos.me_refresh_seconds so roles/permissions stay current.
 */
class EnsureSignedIn
{
    public function __construct(
        private SessionAuth $auth,
        private CampusApi $api,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->auth->check()) {
            return redirect()->guest(Route::has('login') ? route('login') : url('/login'));
        }

        if ($this->auth->meIsStale() && ! $request->hasHeader('X-Livewire')) {
            $this->auth->storeMe($this->api->me()->profile());
        }

        return $next($request);
    }
}
