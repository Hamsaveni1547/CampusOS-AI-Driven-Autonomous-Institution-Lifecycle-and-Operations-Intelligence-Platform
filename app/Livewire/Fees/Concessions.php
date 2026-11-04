<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fee concessions waiting for approval, and past decisions.
 */
#[Layout('layouts::app')]
#[Title('Fee concessions')]
class Concessions extends Component
{
    use InteractsWithApi, WithTable;

    public ?string $rejectId = null;

    public string $remarks = '';

    protected function defaultFilters(): array
    {
        return ['status' => 'pending'];
    }

    public function approve(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('fee-concessions/'.$id.'/decide', ['approve' => true]), onSuccess: fn () => $this->toast('success', __('Concession approved')));
    }

    public function askReject(string $id): void
    {
        $this->rejectId = $id;
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reject-concession');
    }

    public function reject(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('fee-concessions/'.$this->rejectId.'/decide', ['approve' => false, 'remarks' => $this->remarks]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'reject-concession');
            $this->toast('success', __('Concession rejected'));
        });
    }

    public function render(): View
    {
        return view('livewire.fees.concessions', ['rows' => $this->api()->endpoint('fee-concessions')->page($this->activeFilters(null), $this->tableQuery())]);
    }
}
