<?php

namespace App\Support;

use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\ApiUnreachable;
use App\Api\Exceptions\Forbidden;
use App\Api\Exceptions\NotFound;
use App\Api\Exceptions\RateLimited;
use App\Api\Exceptions\Unauthenticated;
use App\Api\Exceptions\ValidationFailed;
use App\Auth\SessionAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns API errors that escape a page into the right response:
 * 401 → sign in again, 422 → back with field errors, 403/404/429/5xx → error pages.
 * Livewire components normally catch ApiException themselves (WithApiErrors).
 */
class ApiErrorRenderer
{
    public function __invoke(ApiException $exception, Request $request): ?Response
    {
        if ($request->expectsJson() && ! $request->hasHeader('X-Livewire')) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
                'errors' => $exception->errors,
                'trace_id' => $exception->traceId,
            ], $exception->status);
        }

        return match (true) {
            $exception instanceof Unauthenticated => $this->signInAgain($request),
            $exception instanceof ValidationFailed => back()->withInput($request->except(['password', 'password_confirmation', 'current_password', 'code']))->withErrors($exception->errors),
            $exception instanceof Forbidden => $this->page(403, $exception),
            $exception instanceof NotFound => $this->page(404, $exception),
            $exception instanceof RateLimited => $this->page(429, $exception),
            $exception instanceof ApiUnreachable => $this->page(503, $exception),
            $exception->status >= 500 => $this->page(500, $exception),
            default => back()->with('toast', ['type' => 'error', 'title' => $exception->getMessage()]),
        };
    }

    private function signInAgain(Request $request): Response
    {
        app(SessionAuth::class)->forget();

        $login = Route::has('login') ? route('login') : url('/login');

        if ($request->hasHeader('X-Livewire')) {
            return response()->json(['redirect' => $login], 401);
        }

        return redirect()->guest($login)->with('status', __('Your session has ended. Please sign in again.'));
    }

    private function page(int $status, ApiException $exception): Response
    {
        return response()->view("errors.{$status}", [
            'message' => $exception->getMessage(),
            'traceId' => $exception->traceId,
            'retryAfter' => $exception->context['retry_after_seconds'] ?? null,
        ], $status);
    }
}
