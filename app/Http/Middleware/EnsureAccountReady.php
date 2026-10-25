<?php

namespace App\Http\Middleware;

use App\Auth\SessionAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users with a pending account requirement (verify email, change password,
 * set up two-step verification) to the screen that resolves it.
 */
class EnsureAccountReady
{
    /**
     * API requirement code → route name of the screen that resolves it.
     */
    public const SCREENS = [
        'EMAIL_VERIFICATION_REQUIRED' => 'required.email',
        'PASSWORD_CHANGE_REQUIRED' => 'required.password',
        'MFA_ENROLLMENT_REQUIRED' => 'required.two-step',
    ];

    public function __construct(private SessionAuth $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requirement = $this->auth->user()?->pendingRequirement();
        $screen = $requirement !== null ? (self::SCREENS[$requirement] ?? null) : null;

        if ($screen !== null && Route::has($screen) && ! $request->routeIs($screen)) {
            return redirect()->route($screen);
        }

        return $next($request);
    }
}
