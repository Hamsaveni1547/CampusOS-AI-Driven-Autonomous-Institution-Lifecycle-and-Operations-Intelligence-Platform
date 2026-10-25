<?php

namespace App\Providers;

use App\Api\CampusApi;
use App\Api\RequestTrace;
use App\Auth\SessionAuth;
use App\Support\Navigation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RequestTrace::class);
        $this->app->scoped(SessionAuth::class, fn ($app) => new SessionAuth($app['session.store']));
        $this->app->scoped(CampusApi::class);
        $this->app->singleton(Navigation::class);
    }

    public function boot(): void
    {
        // Every layout and page can use $currentUser (null for guests).
        View::composer(['components.layout.*', 'livewire.*', 'pages.*', 'errors.*'], function ($view): void {
            $view->with('currentUser', app(SessionAuth::class)->user());
        });

        // @canapi('identity.users.create') ... @endcanapi — show only with (any of) the permission(s).
        Blade::if('canapi', fn (string ...$permissions): bool => (bool) app(SessionAuth::class)->user()?->canAny($permissions));
    }
}
