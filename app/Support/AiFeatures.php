<?php

namespace App\Support;

use App\Api\CampusApi;
use Throwable;

/**
 * Which AI features the backend has switched on (no key, or switched off in settings → buttons stay hidden).
 * Read once per request.
 */
class AiFeatures
{
    /** @var array<string, bool>|null */
    private ?array $status = null;

    public function __construct(private CampusApi $api) {}

    public function on(string $feature): bool
    {
        if ($this->status === null) {
            try {
                $this->status = array_map('boolval', (array) $this->api->get('ai/status', memoize: true)->data());
            } catch (Throwable) {
                $this->status = [];
            }
        }

        return $this->status[$feature] ?? false;
    }
}
