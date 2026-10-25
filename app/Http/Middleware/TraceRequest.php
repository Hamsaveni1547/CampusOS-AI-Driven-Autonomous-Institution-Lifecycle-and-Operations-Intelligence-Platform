<?php

namespace App\Http\Middleware;

use App\Api\RequestTrace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts a new trace id for every browser request; the API receives the same id.
 */
class TraceRequest
{
    public function __construct(private RequestTrace $trace) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->trace->reset();
        Log::shareContext(['trace_id' => $this->trace->id()]);

        $response = $next($request);
        $response->headers->set('X-Trace-Id', $this->trace->id());

        return $response;
    }
}
