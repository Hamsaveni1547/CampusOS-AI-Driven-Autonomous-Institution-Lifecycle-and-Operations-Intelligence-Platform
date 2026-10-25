<?php

namespace App\Api\Exceptions;

/**
 * 422 with field errors — show them under the fields.
 */
class ValidationFailed extends ApiException {}
