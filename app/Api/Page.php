<?php

namespace App\Api;

/**
 * One page of a list from the API (page-based `meta` or cursor-based `meta.next_cursor`).
 */
final class Page
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $currentPage = 1,
        public readonly int $lastPage = 1,
        public readonly int $perPage = 25,
        public readonly ?int $total = null,
        public readonly ?string $nextCursor = null,
    ) {}

    public static function fromResponse(ApiResponse $response): self
    {
        $meta = $response->meta();
        $items = array_values((array) $response->data());

        return new self(
            items: $items,
            currentPage: (int) ($meta['current_page'] ?? 1),
            lastPage: (int) ($meta['last_page'] ?? 1),
            perPage: (int) ($meta['per_page'] ?? max(count($items), 1)),
            total: isset($meta['total']) ? (int) $meta['total'] : null,
            nextCursor: isset($meta['next_cursor']) && is_string($meta['next_cursor']) ? $meta['next_cursor'] : null,
        );
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function from(): int
    {
        return $this->isEmpty() ? 0 : ($this->currentPage - 1) * $this->perPage + 1;
    }

    public function to(): int
    {
        return $this->isEmpty() ? 0 : $this->from() + count($this->items) - 1;
    }
}
