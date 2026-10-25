<?php

namespace App\Api;

use Illuminate\Http\Client\Response;

/**
 * Successful API answer: `{ data, meta?, links?, message? }`.
 */
final class ApiResponse
{
    public function __construct(private Response $response) {}

    /**
     * The `data` member (array for records and lists).
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        return $this->response->json($key === null ? 'data' : 'data.'.$key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return (array) $this->response->json('meta', []);
    }

    public function message(): ?string
    {
        $message = $this->response->json('message');

        return is_string($message) ? $message : null;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        return $this->response->json($key, $default);
    }

    public function status(): int
    {
        return $this->response->status();
    }

    public function raw(): Response
    {
        return $this->response;
    }

    /**
     * Paginated list helper: items plus page info from `meta`.
     */
    public function page(): Page
    {
        return Page::fromResponse($this);
    }
}
