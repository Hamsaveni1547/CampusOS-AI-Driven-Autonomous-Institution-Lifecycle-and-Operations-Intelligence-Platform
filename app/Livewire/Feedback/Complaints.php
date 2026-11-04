<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Complaints for staff who handle them, and complaint categories.
 */
#[Layout('layouts::app')]
#[Title('Complaints')]
class Complaints extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $category = [];

    protected function defaultFilters(): array
    {
        return ['status' => 'open', 'mine' => '', 'overdue' => ''];
    }

    public function openCategory(?string $id = null): void
    {
        $existing = $id ? collect($this->categories())->firstWhere('id', $id) : null;
        $this->category = ['id' => $id ?? '', 'name' => $existing['name'] ?? '', 'sla_hours' => $existing['sla_hours'] ?? 72, 'is_restricted' => $existing['is_restricted'] ?? false, 'status' => $existing['status'] ?? 'active'];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'complaint-category');
    }

    public function saveCategory(): void
    {
        $this->validate(['category.name' => 'required|string|max:100', 'category.sla_hours' => 'required|integer|min:1']);
        $c = $this->category;
        $data = ['name' => $c['name'], 'sla_hours' => (int) $c['sla_hours'], 'is_restricted' => (bool) $c['is_restricted'], 'status' => $c['status']];

        $this->callApi(fn () => $c['id'] ? $this->api()->patch('complaint-categories/'.$c['id'], $data) : $this->api()->post('complaint-categories', $data), fieldMap: ['name' => 'category.name'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'complaint-category');
            $this->toast('success', __('Category saved'));
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        return (array) $this->api()->get('complaint-categories')->data();
    }

    public function render(): View
    {
        return view('livewire.feedback.complaints', [
            'rows' => $this->api()->endpoint('complaints')->page($this->activeFilters(null), $this->tableQuery()),
            'categories' => $this->categories(),
        ]);
    }
}
