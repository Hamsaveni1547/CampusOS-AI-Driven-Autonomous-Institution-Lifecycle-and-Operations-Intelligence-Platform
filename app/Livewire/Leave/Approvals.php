<?php

namespace App\Livewire\Leave;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Staff leave to approve, and who is on leave on a given day.
 */
#[Layout('layouts::app')]
#[Title('Staff leave')]
class Approvals extends Component
{
    use InteractsWithApi, WithTable;

    public ?string $rejectId = null;

    public string $remarks = '';

    protected function defaultFilters(): array
    {
        return ['status' => 'pending', 'from' => '', 'to' => ''];
    }

    public function showStatus(string $status): void
    {
        $this->filters = ['status' => $status, 'from' => '', 'to' => ''];
    }

    public function onLeaveToday(): void
    {
        $this->filters = ['status' => 'approved', 'from' => now()->toDateString(), 'to' => now()->toDateString()];
    }

    public function approve(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('leave-applications/'.$id.'/decide', ['approve' => true]), onSuccess: fn () => $this->toast('success', __('Leave approved')));
    }

    public function askReject(string $id): void
    {
        $this->rejectId = $id;
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reject-leave');
    }

    public function reject(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('leave-applications/'.$this->rejectId.'/decide', ['approve' => false, 'remarks' => $this->remarks]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'reject-leave');
            $this->toast('success', __('Leave rejected'));
        });
    }

    public function render(): View
    {
        return view('livewire.leave.approvals', ['rows' => $this->api()->endpoint('leave-applications')->page($this->activeFilters(null), $this->tableQuery())]);
    }
}
