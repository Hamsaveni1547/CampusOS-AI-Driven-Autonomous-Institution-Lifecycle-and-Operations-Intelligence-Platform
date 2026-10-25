<?php

namespace App\Api\Exceptions;

/**
 * The API could not be reached (down, timeout, DNS). Shown as "service unavailable".
 */
class ApiUnreachable extends ApiException
{
    public static function because(string $traceId): self
    {
        return new self(__('CampusOS is not reachable right now. Please try again in a moment.'), 503, 'API_UNREACHABLE', traceId: $traceId);
    }
}
