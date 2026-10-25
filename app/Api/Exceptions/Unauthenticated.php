<?php

namespace App\Api\Exceptions;

/**
 * 401 — the token is missing, expired or revoked. The user must sign in again.
 */
class Unauthenticated extends ApiException {}
