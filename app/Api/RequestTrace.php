<?php

namespace App\Api;

use Illuminate\Support\Str;

/**
 * One trace id per browser request, sent to the API as X-Trace-Id so frontend and
 * backend logs share it. Reset by the TraceRequest middleware on every request.
 */
final class RequestTrace
{
    private ?string $id = null;

    public function id(): string
    {
        return $this->id ??= (string) Str::uuid();
    }

    public function reset(): void
    {
        $this->id = null;
    }
}
