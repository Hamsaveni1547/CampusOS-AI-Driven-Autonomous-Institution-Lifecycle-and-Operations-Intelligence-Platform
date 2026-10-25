<?php

namespace App\Api\Exceptions;

/**
 * 4xx without field errors — show the message (and act on the code).
 */
class BusinessRuleFailed extends ApiException {}
