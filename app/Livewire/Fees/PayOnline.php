<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Simulated payment gateway (demo): pick a method, then choose whether the payment succeeds or fails.
 * No card or bank details are asked for.
 */
#[Layout('layouts::app')]
#[Title('Pay fees online')]
class PayOnline extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $paymentId = '';

    public string $method = 'upi';

    public function mount(string $payment): void
    {
        $this->paymentId = $payment;
    }

    public function complete(string $outcome): void
    {
        $this->validate(['method' => 'required|in:upi,card,netbanking']);

        $this->callApi(fn () => $this->api()->post('me/online-payments/'.$this->paymentId.'/complete', ['method' => $this->method, 'outcome' => $outcome === 'success' ? 'success' : 'failure']),
            onSuccess: fn ($response) => $response->data('status') === 'success' ? $this->toast('success', __('Payment successful')) : $this->toast('error', __('Payment failed')));
    }

    public function retry(): void
    {
        $this->callApi(fn () => $this->api()->post('me/online-payments'), onSuccess: fn ($response) => $this->redirectRoute('fees.pay', $response->data('id'), navigate: true));
    }

    public function render(): View
    {
        $payment = collect((array) $this->api()->withoutCache(fn () => $this->api()->get('me/online-payments'))->data())->firstWhere('id', $this->paymentId);
        abort_if($payment === null, 404);

        return view('livewire.fees.pay-online', ['payment' => $payment]);
    }
}
