<?php

namespace Tests\Feature\Fees;

use App\Livewire\Fees\PayOnline;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class OnlinePaymentScreenTest extends TestCase
{
    public function test_student_simulates_failure_then_success(): void
    {
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['roles' => ['student']]);
        $state = ['status' => 'pending', 'receipt' => null];
        Http::fake([
            self::API.'me/online-payments/op1/complete' => function (Request $r) use (&$state) {
                $state = $r['outcome'] === 'success' ? ['status' => 'success', 'receipt' => ['id' => 'r1', 'receipt_no' => 'BIT/RCPT/2026/000099']] : ['status' => 'failed', 'receipt' => null];

                return Http::response(['data' => ['id' => 'op1', ...$state]]);
            },
            self::API.'me/online-payments' => function (Request $r) use (&$state) {
                return $r->method() === 'POST'
                    ? Http::response(['data' => ['id' => 'op2']], 201)
                    : Http::response(['data' => [['id' => 'op1', 'amount' => 5000, 'method' => 'upi', 'gateway_ref' => 'SIMABC', 'failure_reason' => 'Declined by the bank (simulated).', ...$state]]]);
            },
        ]);

        $page = Livewire::test(PayOnline::class, ['payment' => 'op1'])
            ->assertSee('₹5,000.00')->assertSee('Simulate success')
            ->set('method', 'card')
            ->call('complete', 'failure')
            ->assertSee('Payment failed');

        $state = ['status' => 'pending', 'receipt' => null];
        $page->call('complete', 'success')->assertSee('BIT/RCPT/2026/000099');
        Livewire::test(PayOnline::class, ['payment' => 'op1'])->call('retry')->assertRedirect(route('fees.pay', 'op2'));

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'op1/complete') && $r['method'] === 'card' && $r['outcome'] === 'failure');
    }
}
