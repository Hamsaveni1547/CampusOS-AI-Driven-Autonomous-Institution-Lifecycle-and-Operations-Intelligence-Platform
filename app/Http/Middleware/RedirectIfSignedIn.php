<?php

namespace App\Http\Middleware;

use App\Auth\SessionAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sign-in pages are not shown to users who are already signed in.
 */
class RedirectIfSignedIn
{
    public function __construct(private SessionAuth $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->auth->check()) {
            return redirect()->intended(Route::has('home') ? route('home') : '/');
        }

        return $next($request);
    }
}
