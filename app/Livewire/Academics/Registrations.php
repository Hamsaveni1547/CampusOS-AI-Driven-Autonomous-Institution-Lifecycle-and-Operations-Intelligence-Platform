<?php

namespace App\Livewire\Academics;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Review queue of student term registrations, with bulk approve.
 */
#[Layout('layouts::app')]
#[Title('Term registrations')]
class Registrations extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<int, string> selected registration ids (checkbox arrays can have gaps) */
    public array $selected = [];

    public string $remarks = '';

    protected function defaultFilters(): array
    {
        return ['term' => '', 'status' => 'submitted', 'programme' => ''];
    }

    public function updatedFilters(): void
    {
        $this->page = 1;
        $this->selected = [];
    }

    /**
     * @param  array<int, string>|null  $ids  null = the selected rows
     */
    public function approve(?array $ids = null): void
    {
        $this->decide($ids ?? $this->selected, true);
    }

    public function askReject(?string $id = null): void
    {
        if ($id !== null) {
            $this->selected = [$id];
        }

        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reject-registrations');
    }

    public function reject(): void
    {
        $this->validate(['remarks' => 'required|string|max:1000']);
        $this->decide($this->selected, false);
        $this->dispatch('close-dialog', 'reject-registrations');
    }

    /**
     * @param  array<int, string>  $ids
     */
    private function decide(array $ids, bool $approve): void
    {
        if ($ids === []) {
            return;
        }

        $response = $this->callApi(fn () => $this->api()->post('term-registrations/decide', ['ids' => array_values($ids), 'approve' => $approve, 'remarks' => $this->remarks ?: null]));

        if ($response !== null) {
            $this->selected = [];
            $this->toast('success', (string) $response->data('message'));
        }
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $programmes = $lookups->programmes(false);
        $statuses = ['submitted' => __('Waiting'), 'approved' => __('Approved'), 'rejected' => __('Rejected')];

        return view('livewire.academics.registrations', [
            'rows' => $this->api()->endpoint('term-registrations')->page($this->activeFilters(), $this->tableQuery()),
            'terms' => $lookups->terms(),
            'programmes' => $programmes,
            'statuses' => $statuses,
            'chips' => $this->filterChips(['programme' => ['_name' => __('Programme')] + $programmes, 'status' => ['_name' => __('Status')] + $statuses]),
        ]);
    }
}
