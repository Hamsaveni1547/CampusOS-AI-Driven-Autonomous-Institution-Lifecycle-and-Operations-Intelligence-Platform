<?php

namespace App\Api\Resources;

use App\Api\ApiResponse;
use App\Api\CampusApi;
use App\Api\Page;

/**
 * A REST collection on the API, e.g. `departments`:
 * list → GET /departments, find → GET /departments/{id}, create → POST, update → PATCH,
 * delete → DELETE, action → POST /departments/{id}/{action}.
 */
final class Endpoint
{
    public function __construct(
        private CampusApi $api,
        private string $path,
    ) {}

    /**
     * All rows of a non-paginated list.
     *
     * @param  array<string, mixed>  $filters  sent as filter[...]
     * @param  array<string, mixed>  $query  other query params (sort, include…)
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = [], array $query = [], bool $memoize = true): array
    {
        return array_values((array) $this->api->get($this->path, $this->query($filters, $query), $memoize)->data());
    }

    /**
     * One page of a paginated list.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $query
     */
    public function page(array $filters = [], array $query = []): Page
    {
        return $this->api->get($this->path, $this->query($filters, $query))->page();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id): array
    {
        return (array) $this->api->get($this->path.'/'.$id)->data();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ApiResponse
    {
        return $this->api->post($this->path, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): ApiResponse
    {
        return $this->api->patch($this->path.'/'.$id, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function delete(string $id, array $data = []): ApiResponse
    {
        return $this->api->delete($this->path.'/'.$id, $data);
    }

    /**
     * POST /{path}/{id}/{action}
     *
     * @param  array<string, mixed>  $data
     */
    public function action(string $id, string $action, array $data = []): ApiResponse
    {
        return $this->api->post($this->path.'/'.$id.'/'.$action, $data);
    }

    /**
     * Nested collection, e.g. `$api->endpoint('batches')->nested($id, 'sections')`.
     */
    public function nested(string $id, string $child): self
    {
        return new self($this->api, $this->path.'/'.$id.'/'.$child);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function query(array $filters, array $query): array
    {
        return $filters === [] ? $query : array_merge($query, ['filter' => $filters]);
    }
}
