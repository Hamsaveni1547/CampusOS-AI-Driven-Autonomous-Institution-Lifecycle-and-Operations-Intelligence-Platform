<?php

namespace App\Api\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * An error answer from the CampusOS API, in its standard envelope
 * `{ message, code, errors?, context?, trace_id }`.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly string $errorCode,
        public readonly array $errors = [],
        public readonly array $context = [],
        public readonly ?string $traceId = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        $message = is_string($body['message'] ?? null) && $body['message'] !== ''
            ? $body['message']
            : __('Something went wrong. Please try again.');

        $code = is_string($body['code'] ?? null) ? $body['code'] : 'HTTP_'.$response->status();
        $errors = is_array($body['errors'] ?? null) ? $body['errors'] : [];
        $context = is_array($body['context'] ?? null) ? $body['context'] : [];
        $traceId = is_string($body['trace_id'] ?? null) ? $body['trace_id'] : $response->header('X-Trace-Id');

        $class = match (true) {
            $response->status() === 401 => Unauthenticated::class,
            $response->status() === 403 => Forbidden::class,
            $response->status() === 404 => NotFound::class,
            $response->status() === 409 => Conflict::class,
            $response->status() === 422 && $errors !== [] => ValidationFailed::class,
            $response->status() === 429 => RateLimited::class,
            $response->status() >= 500 => ServerError::class,
            default => BusinessRuleFailed::class,
        };

        return new $class($message, $response->status(), $code, $errors, $context, $traceId !== '' ? $traceId : null);
    }

    public function is(string $code): bool
    {
        return $this->errorCode === $code;
    }

    /**
     * First error message for a field (validation errors).
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
