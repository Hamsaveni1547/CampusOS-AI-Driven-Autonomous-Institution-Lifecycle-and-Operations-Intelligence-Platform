<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The student's fees: what is due, what was paid, and receipts.
 */
#[Layout('layouts::app')]
#[Title('My fees')]
class MyFees extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        $options = (array) $this->api()->get('finance/options', memoize: true)->data();

        return view('livewire.fees.my-fees', [
            'account' => (array) $this->api()->get('me/fees')->data(),
            'modes' => collect($options['modes'])->mapWithKeys(fn (array $m) => [$m['value'] => $m['label']])->all(),
        ]);
    }
}
