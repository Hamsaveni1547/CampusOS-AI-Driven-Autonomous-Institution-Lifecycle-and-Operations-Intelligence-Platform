<?php

namespace App\Api\Exceptions;

/**
 * 5xx — the API failed. Show the trace id so support can find it.
 */
class ServerError extends ApiException {}
