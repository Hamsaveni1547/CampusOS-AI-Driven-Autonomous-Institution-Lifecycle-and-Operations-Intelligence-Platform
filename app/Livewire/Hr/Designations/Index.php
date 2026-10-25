<?php

namespace App\Livewire\Hr\Designations;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\ManagesRecords;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Designations')]
class Index extends Component
{
    use InteractsWithApi, ManagesRecords, WithTable;

    protected function defaultFilters(): array
    {
        return ['category' => '', 'status' => ''];
    }

    protected function recordsPath(): string
    {
        return 'designations';
    }

    protected function recordLabel(): string
    {
        return __('Designation');
    }

    protected function findRecord(string $id): array
    {
        return collect($this->api()->endpoint('designations')->all(memoize: false))->firstWhere('id', $id) ?? [];
    }

    protected function emptyForm(): array
    {
        return ['name' => '', 'category' => 'teaching', 'rank' => '100', 'status' => 'active'];
    }

    protected function toForm(array $record): array
    {
        return ['name' => $record['name'], 'category' => $record['category'], 'rank' => (string) $record['rank'], 'status' => $record['status']];
    }

    protected function toPayload(): array
    {
        return ['name' => trim($this->form['name']), 'category' => $this->form['category'], 'rank' => (int) ($this->form['rank'] ?: 100), 'status' => $this->form['status']];
    }

    protected function formRules(): array
    {
        return ['form.name' => 'required|string|max:100', 'form.category' => 'required|in:teaching,non_teaching', 'form.rank' => 'nullable|integer|min:1|max:1000'];
    }

    public function render(): View
    {
        return view('livewire.hr.designations.index', [
            'designations' => $this->api()->endpoint('designations')->all($this->activeFilters(null), memoize: false),
            'canManage' => $this->can('hr.designations.manage'),
            'chips' => $this->filterChips([
                'category' => ['_name' => __('Category'), 'teaching' => __('Teaching'), 'non_teaching' => __('Non-teaching')],
                'status' => ['_name' => __('Status'), 'active' => __('Active'), 'inactive' => __('Inactive')],
            ]),
        ]);
    }
}
