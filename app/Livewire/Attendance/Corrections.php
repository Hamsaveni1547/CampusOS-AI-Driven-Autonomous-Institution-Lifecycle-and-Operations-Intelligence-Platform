<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Attendance correction requests to approve or reject.
 */
#[Layout('layouts::app')]
#[Title('Attendance corrections')]
class Corrections extends Component
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
        $this->callApi(fn () => $this->api()->post('attendance-corrections/'.$id.'/decide', ['approve' => true]), onSuccess: fn () => $this->toast('success', __('Attendance corrected')));
    }

    public function askReject(string $id): void
    {
        $this->rejectId = $id;
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reject-correction');
    }

    public function reject(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('attendance-corrections/'.$this->rejectId.'/decide', ['approve' => false, 'remarks' => $this->remarks]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'reject-correction');
            $this->toast('success', __('Request rejected'));
        });
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('attendance/options', memoize: true)->data();

        return view('livewire.attendance.corrections', [
            'rows' => $this->api()->endpoint('attendance-corrections')->page($this->activeFilters(null), $this->tableQuery()),
            'labels' => collect($options['statuses'])->mapWithKeys(fn (array $s) => [$s['value'] => $s['label']])->all(),
        ]);
    }
}
