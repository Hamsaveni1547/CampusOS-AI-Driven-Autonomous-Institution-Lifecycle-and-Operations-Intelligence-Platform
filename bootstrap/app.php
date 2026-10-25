<?php

use App\Api\Exceptions\ApiException;
use App\Http\Middleware\EnsureAccountReady;
use App\Http\Middleware\EnsureSignedIn;
use App\Http\Middleware\RedirectIfSignedIn;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TraceRequest;
use App\Support\ApiErrorRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [TraceRequest::class], append: [SecurityHeaders::class]);

        $middleware->alias([
            'campus.auth' => EnsureSignedIn::class,
            'campus.guest' => RedirectIfSignedIn::class,
            'campus.ready' => EnsureAccountReady::class,
            'permission' => RequirePermission::class,
        ]);

        // The trusted-device cookie is encrypted like every other cookie; the theme cookie is read by JS.
        $middleware->encryptCookies(except: ['campus_theme']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (ApiException $exception, Request $request) => app(ApiErrorRenderer::class)($exception, $request));

        $exceptions->dontReport([ApiException::class]);
    })->create();
