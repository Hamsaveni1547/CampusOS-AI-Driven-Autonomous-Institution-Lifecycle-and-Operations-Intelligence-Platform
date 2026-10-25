<?php

namespace App\Api\Exceptions;

/**
 * 403 — the signed-in user may not do this (or a pending account requirement blocks it).
 */
class Forbidden extends ApiException {}
