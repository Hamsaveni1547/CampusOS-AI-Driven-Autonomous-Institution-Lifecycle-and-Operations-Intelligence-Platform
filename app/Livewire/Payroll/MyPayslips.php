<?php

namespace App\Livewire\Payroll;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The signed-in staff member's payslips.
 */
#[Layout('layouts::app')]
#[Title('My payslips')]
class MyPayslips extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        return view('livewire.payroll.my-payslips', ['payslips' => (array) $this->api()->get('me/payslips')->data()]);
    }
}
