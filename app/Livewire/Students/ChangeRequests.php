<?php

namespace App\Livewire\Students;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Queue of student profile correction requests.
 */
#[Layout('layouts::app')]
#[Title('Correction requests')]
class ChangeRequests extends Component
{
    use InteractsWithApi, WithTable;

    public ?string $rejectId = null;

    public string $remarks = '';

    protected function defaultFilters(): array
    {
        return ['status' => 'pending'];
    }

    public function approve(string $requestId): void
    {
        $this->callApi(fn () => $this->api()->post('student-change-requests/'.$requestId.'/review', ['approved' => true]), onSuccess: fn () => $this->toast('success', __('Correction applied')));
    }

    public function askReject(string $requestId): void
    {
        $this->rejectId = $requestId;
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reject-change');
    }

    public function reject(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('student-change-requests/'.$this->rejectId.'/review', ['approved' => false, 'remarks' => $this->remarks]), onSuccess: function (): void {
            $this->rejectId = null;
            $this->dispatch('close-dialog', 'reject-change');
            $this->toast('success', __('Request rejected'));
        });
    }

    public function render(): View
    {
        return view('livewire.students.change-requests', [
            'rows' => $this->api()->endpoint('student-change-requests')->page($this->activeFilters(null), $this->tableQuery()),
        ]);
    }
}
