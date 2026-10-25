<?php

namespace App\Livewire\Concerns;

use App\Api\CampusApi;
use App\Auth\CurrentUser;
use App\Auth\SessionAuth;

/**
 * Access to the API client and the signed-in user from Livewire components.
 * (Resolved per call so long-lived component instances never keep stale state.)
 */
trait InteractsWithApi
{
    use WithApiErrors;

    protected function api(): CampusApi
    {
        return app(CampusApi::class);
    }

    protected function user(): ?CurrentUser
    {
        return app(SessionAuth::class)->user();
    }

    protected function can(string ...$permissions): bool
    {
        return (bool) $this->user()?->canAny($permissions);
    }

    protected function authorizeApi(string ...$permissions): void
    {
        abort_unless($this->can(...$permissions), 403);
    }
}
