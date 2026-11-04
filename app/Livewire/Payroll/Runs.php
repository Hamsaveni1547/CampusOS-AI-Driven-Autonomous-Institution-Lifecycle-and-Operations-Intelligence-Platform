<?php

namespace App\Livewire\Payroll;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Monthly payroll runs, and starting a new month.
 */
#[Layout('layouts::app')]
#[Title('Payroll')]
class Runs extends Component
{
    use InteractsWithApi;

    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function create(): void
    {
        $this->validate(['month' => 'required|date_format:Y-m']);

        $this->callApi(fn () => $this->api()->post('payroll-runs', ['month' => $this->month]), fieldMap: ['month' => 'month'], onSuccess: fn ($response) => $this->redirectRoute('payroll-runs.show', $response->data('id'), navigate: true));
    }

    public function render(): View
    {
        return view('livewire.payroll.runs', ['runs' => (array) $this->api()->get('payroll-runs')->data()]);
    }
}
