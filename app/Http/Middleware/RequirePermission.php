<?php

namespace App\Http\Middleware;

use App\Auth\SessionAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `->middleware('permission:identity.users.view,identity.users.create')` — any of the listed
 * permissions. `permission:platform` restricts to platform administrators.
 * The API enforces permissions too; this only avoids showing pages that would fail.
 */
class RequirePermission
{
    public function __construct(private SessionAuth $auth) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $this->auth->user();

        $allowed = $user !== null && (
            $permissions === ['platform']
                ? $user->isPlatformAdmin()
                : $user->canAny($permissions)
        );

        abort_unless($allowed, 403);

        return $next($request);
    }
}
