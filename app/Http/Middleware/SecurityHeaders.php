<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict security headers. Scripts and styles come only from this app; the few
 * inline scripts (theme bootstrap, Livewire config) carry a per-request nonce.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $devServer = app()->environment('local') ? ' http://localhost:5173 ws://localhost:5173 http://127.0.0.1:5173 ws://127.0.0.1:5173 http://[::1]:5173 ws://[::1]:5173' : '';

        $policy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$devServer}",
            "style-src 'self' 'unsafe-inline'{$devServer}",
            "img-src 'self' data: blob:",
            "font-src 'self' data:{$devServer}",
            "connect-src 'self'{$devServer}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
