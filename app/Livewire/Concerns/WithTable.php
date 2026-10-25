<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Search, filters, sort and paging kept in the URL (shareable links, back button works).
 * Used with <x-ui.filters>, <x-ui.th sortable>, <x-ui.pagination>.
 *
 * The component builds its API query with tableQuery() and declares its filters:
 *     protected function defaultFilters(): array { return ['status' => '', 'department' => '']; }
 */
trait WithTable
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: '')]
    public string $sort = '';

    #[Url(as: 'page', except: 1)]
    public int $page = 1;

    #[Url(as: 'per_page', except: 25)]
    public int $perPage = 25;

    /**
     * @var array<string, mixed>
     */
    #[Url(as: 'f')]
    public array $filters = [];

    /**
     * Filter keys with their empty values; override in the component.
     *
     * @return array<string, mixed>
     */
    protected function defaultFilters(): array
    {
        return [];
    }

    public function mountWithTable(): void
    {
        $this->filters = array_merge($this->defaultFilters(), $this->filters);
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedFilters(): void
    {
        $this->page = 1;
    }

    public function sortBy(string $column): void
    {
        $this->sort = match ($this->sort) {
            $column => '-'.$column,
            '-'.$column => '',
            default => $column,
        };

        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function setPerPage(int|string $perPage): void
    {
        $this->perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 25;
        $this->page = 1;
    }

    public function removeFilter(string $key): void
    {
        $this->filters[$key] = is_array($this->filters[$key] ?? null) ? [] : '';
        $this->page = 1;
    }

    public function resetFilters(): void
    {
        foreach (array_keys($this->filters) as $key) {
            $this->filters[$key] = is_array($this->filters[$key]) ? [] : '';
        }

        $this->search = '';
        $this->page = 1;
    }

    /**
     * Filters with a value, for the API's filter[...] parameters.
     *
     * @param  string|null  $searchKey  API filter name for the search box (null to skip)
     * @return array<string, mixed>
     */
    protected function activeFilters(?string $searchKey = 'search'): array
    {
        $filters = array_filter($this->filters, fn ($value) => $value !== '' && $value !== null && $value !== []);

        if ($searchKey !== null && trim($this->search) !== '') {
            $filters[$searchKey] = trim($this->search);
        }

        return $filters;
    }

    /**
     * Query parameters other than filters: page, per_page, sort.
     *
     * @return array<string, mixed>
     */
    protected function tableQuery(): array
    {
        return array_filter([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort' => $this->sort,
        ], fn ($value) => $value !== '');
    }

    /**
     * Chips for <x-ui.filters :active>: [label => key].
     *
     * @param  array<string, array<int|string, string>|string>  $labels  key => label, or key => [value => label] (numeric values become int keys)
     * @return array<string, string>
     */
    protected function filterChips(array $labels): array
    {
        $chips = [];

        foreach ($this->filters as $key => $value) {
            if ($value === '' || $value === null || $value === []) {
                continue;
            }

            $label = $labels[$key] ?? $key;
            $display = is_array($label) ? ($label[(string) $value] ?? $value) : $value;
            $name = is_array($label) ? ($label['_name'] ?? ucfirst(str_replace('_', ' ', $key))) : $label;

            $chips[$name.': '.$display] = $key;
        }

        return $chips;
    }
}
