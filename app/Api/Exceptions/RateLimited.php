<?php

namespace App\Api\Exceptions;

/**
 * 429 — too many attempts; context.retry_after_seconds may tell how long to wait.
 */
class RateLimited extends ApiException {}
