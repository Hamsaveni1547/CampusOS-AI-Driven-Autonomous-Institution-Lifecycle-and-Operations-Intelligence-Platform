<?php

namespace App\Livewire\Payroll;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Printable payslip (payroll staff, or the employee once finalised).
 */
#[Layout('layouts::app')]
class PayslipShow extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $payslipId = '';

    public function mount(string $payslip): void
    {
        $this->payslipId = $payslip;
    }

    public function render(): View
    {
        $slip = (array) $this->api()->get('payslips/'.$this->payslipId)->data();

        return view('livewire.payroll.payslip-show', [
            'slip' => $slip,
            'monthName' => Carbon::createFromFormat('Y-m', $slip['run']['month'])->format('F Y'),
            'institution' => $this->user()?->institution()['name'] ?? '',
        ])->title(__('Payslip :m', ['m' => $slip['run']['month']]));
    }
}
