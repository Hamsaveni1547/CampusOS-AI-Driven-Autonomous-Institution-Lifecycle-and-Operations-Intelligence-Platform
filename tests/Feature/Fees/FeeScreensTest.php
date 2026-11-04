<?php

namespace Tests\Feature\Fees;

use App\Livewire\Fees\Collect;
use App\Livewire\Fees\Concessions;
use App\Livewire\Fees\MyFees;
use App\Livewire\Fees\Receipt;
use App\Livewire\Fees\Reports;
use App\Livewire\Fees\Setup;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class FeeScreensTest extends TestCase
{
    private const META = ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['finance.structures.manage', 'finance.demands.manage', 'finance.payments.collect', 'finance.payments.cancel', 'finance.concessions.approve', 'finance.reports.view'], 'roles' => ['finance_officer']]);
        Http::fake([self::API.'finance/options' => Http::response(['data' => [
            'modes' => [['value' => 'cash', 'label' => 'Cash'], ['value' => 'upi', 'label' => 'UPI']],
            'demand_statuses' => [], 'quotas' => [['value' => 'cet', 'label' => 'CET']], 'heads' => [['value' => 'h1', 'label' => 'Tuition fee']],
        ]])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function account(float $balance): array
    {
        return [
            'student' => ['id' => 'st1', 'name' => 'Asha', 'register_no' => 'BIT26MCA001', 'programme' => 'MCA'],
            'totals' => ['billed' => 52500.0, 'concession' => 0.0, 'paid' => 52500.0 - $balance, 'balance' => $balance, 'overdue' => $balance, 'no_dues' => $balance <= 0],
            'demands' => [['id' => 'd1', 'student' => null, 'title' => 'MCA semester 1 fees', 'due_on' => '2026-10-01', 'amount' => 52500.0, 'concession' => 0.0, 'paid' => 52500.0 - $balance, 'balance' => $balance, 'status' => 'open', 'overdue' => true, 'cancel_reason' => null, 'items' => [['head' => 'Tuition fee', 'amount' => 52500.0]]]],
            'payments' => [['id' => 'p1', 'receipt_no' => 'BIT/RCPT/2026/000001', 'student' => null, 'amount' => 1000.0, 'mode' => 'cash', 'reference' => null, 'paid_on' => '2026-10-02', 'status' => 'valid', 'cancel_reason' => null, 'received_by' => 'Mohan']],
        ];
    }

    public function test_counter_finds_a_student_takes_payment_and_asks_for_a_concession(): void
    {
        Http::fake([
            self::API.'students?*' => Http::response(['data' => [['id' => 'st1', 'name' => 'Asha', 'register_no' => 'BIT26MCA001']], 'meta' => self::META]),
            self::API.'students/st1/fee-account' => Http::response(['data' => $this->account(51500.0)]),
            self::API.'fee-payments' => Http::response(['data' => ['id' => 'p2', 'receipt_no' => 'BIT/RCPT/2026/000002']], 201),
            self::API.'fee-demands/d1/concessions' => Http::response(['data' => ['id' => 'c1']], 201),
        ]);

        Livewire::test(Collect::class)
            ->set('search', 'Asha')
            ->assertSee('BIT26MCA001')
            ->call('choose', 'st1')
            ->assertSee('MCA semester 1 fees')
            ->assertSee('BIT/RCPT/2026/000001')
            ->assertSet('payment.amount', 51500.0)
            ->set('payment.amount', '20000')
            ->call('collect')
            ->assertHasNoErrors()
            ->call('openConcession', 'd1', 'MCA semester 1 fees')
            ->set('concession.amount', '5000')
            ->set('concession.reason', 'Merit scholarship')
            ->call('requestConcession')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'fee-payments') && $r['student_id'] === 'st1' && $r['amount'] === 20000.0 && $r['mode'] === 'cash');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'd1/concessions') && $r['amount'] === 5000.0);
    }

    public function test_structure_is_saved_and_fees_raised(): void
    {
        Http::fake([
            self::API.'fee-heads' => Http::response(['data' => [['id' => 'h1', 'code' => 'TUI', 'name' => 'Tuition fee', 'description' => null, 'status' => 'active']]]),
            self::API.'fee-structures' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 's2']], 201) : Http::response(['data' => [['id' => 's1', 'name' => 'MCA sem 1', 'programme' => ['id' => 'pr1', 'code' => 'MCA', 'name' => 'MCA'], 'admission_year' => 2026, 'quota' => null, 'term_number' => 1, 'due_on' => '2026-10-01', 'status' => 'active', 'total' => 52500.0, 'items' => [['fee_head_id' => 'h1', 'head' => 'Tuition fee', 'amount' => 52500.0]], 'demands' => 40]]]),
            self::API.'fee-structures/s1/generate' => Http::response(['data' => ['raised' => 3, 'message' => 'Fees raised for 3 students.']]),
            self::API.'programmes*' => Http::response(['data' => [['id' => 'pr1', 'code' => 'MCA', 'name' => 'MCA']]]),
        ]);

        Livewire::test(Setup::class)
            ->assertSee('MCA sem 1')
            ->assertSee('₹52,500.00')
            ->call('openStructure')
            ->set('structure.name', 'MBA sem 1')
            ->set('structure.programme_id', 'pr1')
            ->set('structure.items.0.fee_head_id', 'h1')
            ->set('structure.items.0.amount', '60000')
            ->call('saveStructure')
            ->assertHasNoErrors()
            ->call('generate', 's1');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'fee-structures') && $r['items'] === [['fee_head_id' => 'h1', 'amount' => 60000.0]] && $r['term_number'] === null);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 's1/generate'));
    }

    public function test_reports_concessions_receipt_and_student_view(): void
    {
        Http::fake([
            self::API.'finance/reports/dues*' => Http::response(['data' => ['total' => 51500.0, 'students' => 1, 'rows' => [['student' => ['id' => 'st1', 'name' => 'Asha', 'register_no' => 'R1', 'programme' => 'MCA'], 'demands' => 1, 'balance' => 51500.0, 'oldest_due' => '2026-10-01']]]]),
            self::API.'programmes*' => Http::response(['data' => []]),
            self::API.'fee-concessions?*' => Http::response(['data' => [['id' => 'c1', 'demand' => ['id' => 'd1', 'title' => 'MCA sem 1', 'amount' => 52500.0, 'balance' => 51500.0], 'student' => ['id' => 'st1', 'name' => 'Asha', 'register_no' => 'R1'], 'amount' => 5000.0, 'reason' => 'Merit', 'status' => 'pending', 'remarks' => null, 'requested_by' => 'Mohan', 'requested_at' => now()->toIso8601String(), 'can_decide' => true]], 'meta' => self::META]),
            self::API.'fee-concessions/c1/decide' => Http::response(['message' => 'ok']),
            self::API.'fee-payments/p1' => Http::response(['data' => ['id' => 'p1', 'receipt_no' => 'BIT/RCPT/2026/000001', 'student' => ['id' => 'st1', 'name' => 'Asha', 'register_no' => 'R1'], 'amount' => 1000.0, 'mode' => 'cash', 'reference' => null, 'paid_on' => '2026-10-02', 'status' => 'valid', 'cancel_reason' => null, 'received_by' => 'Mohan', 'programme' => 'MCA', 'paid_for' => [['demand' => 'MCA sem 1', 'amount' => 1000.0]], 'balance_after' => 51500.0]]),
            self::API.'me/fees' => Http::response(['data' => $this->account(0.0)]),
        ]);

        Livewire::test(Reports::class)->assertSee('₹51,500.00')->assertSee('Asha');
        Livewire::test(Concessions::class)->assertSee('Merit')->call('approve', 'c1');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'c1/decide') && $r['approve'] === true);
        Livewire::test(Receipt::class, ['payment' => 'p1'])->assertSeeInOrder(['BIT/RCPT/2026/000001', 'Asha', 'MCA sem 1', '₹1,000.00']);
        Livewire::test(MyFees::class)->assertSee('₹0.00')->assertSee('BIT/RCPT/2026/000001');
    }
}
