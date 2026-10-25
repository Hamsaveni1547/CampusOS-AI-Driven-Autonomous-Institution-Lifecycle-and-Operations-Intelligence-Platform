<?php

namespace App\Api\Exceptions;

/**
 * 409 — the request conflicts with the current state (already done, in use, closed period).
 */
class Conflict extends ApiException {}
