<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Printable fee receipt (for staff and the student who paid).
 */
#[Layout('layouts::app')]
class Receipt extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $paymentId = '';

    public function mount(string $payment): void
    {
        $this->paymentId = $payment;
    }

    public function render(): View
    {
        $receipt = (array) $this->api()->get('fee-payments/'.$this->paymentId)->data();
        $options = (array) $this->api()->get('finance/options', memoize: true)->data();

        return view('livewire.fees.receipt', [
            'receipt' => $receipt,
            'mode' => collect($options['modes'])->firstWhere('value', $receipt['mode'])['label'] ?? $receipt['mode'],
            'institution' => $this->user()?->institution()['name'] ?? '',
        ])->title(__('Receipt :no', ['no' => $receipt['receipt_no']]));
    }
}
