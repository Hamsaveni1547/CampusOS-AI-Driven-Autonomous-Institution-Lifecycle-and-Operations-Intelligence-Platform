<?php

namespace Tests\Feature\Payroll;

use App\Livewire\Payroll\MyPayslips;
use App\Livewire\Payroll\PayslipShow;
use App\Livewire\Payroll\Runs;
use App\Livewire\Payroll\RunShow;
use App\Livewire\Payroll\Salaries;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['payroll.salaries.manage', 'payroll.runs.process', 'payroll.runs.approve', 'payroll.reports.view'], 'roles' => ['finance_officer']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payslip(): array
    {
        return [
            'id' => 'ps1', 'employee' => ['code' => 'BITEMP0010', 'name' => 'Dr. Kavya', 'department' => 'CSE'], 'days_in_month' => 30, 'lop_days' => 0.0,
            'adjustments' => [], 'gross' => 90000.0, 'deductions' => 2000.0, 'net' => 88000.0,
            'lines' => [['code' => 'BASIC', 'name' => 'Basic pay', 'kind' => 'earning', 'amount' => 60000.0], ['code' => 'DA', 'name' => 'Dearness allowance', 'kind' => 'earning', 'amount' => 30000.0], ['code' => 'PF', 'name' => 'Provident fund', 'kind' => 'deduction', 'amount' => 1800.0], ['code' => 'PT', 'name' => 'Professional tax', 'kind' => 'deduction', 'amount' => 200.0]],
        ];
    }

    public function test_salary_is_set_for_an_employee(): void
    {
        Http::fake([
            self::API.'pay-components' => Http::response(['data' => [
                ['id' => 'c1', 'code' => 'BASIC', 'name' => 'Basic pay', 'kind' => 'earning', 'calculation' => 'fixed', 'percent' => null, 'max_amount' => null, 'affected_by_lop' => true, 'position' => 1, 'status' => 'active'],
                ['id' => 'c2', 'code' => 'DA', 'name' => 'Dearness allowance', 'kind' => 'earning', 'calculation' => 'percent', 'percent' => 50.0, 'max_amount' => null, 'affected_by_lop' => true, 'position' => 2, 'status' => 'active'],
            ]]),
            self::API.'payroll/salaries*' => Http::response(['data' => [['id' => 'e1', 'code' => 'BITEMP0010', 'name' => 'Dr. Kavya', 'department' => 'CSE', 'designation' => 'Professor', 'status' => 'active', 'gross' => null, 'net' => null]]]),
            self::API.'employees/e1/salary' => fn (Request $r) => Http::response(['data' => [
                'employee' => ['id' => 'e1', 'name' => 'Dr. Kavya'],
                'components' => [['id' => 'c1', 'calculation' => 'fixed', 'assigned' => false, 'amount' => null], ['id' => 'c2', 'calculation' => 'percent', 'assigned' => false, 'amount' => null]],
                'preview' => null,
            ]]),
        ]);

        Livewire::test(Salaries::class)
            ->assertSee('Dr. Kavya')
            ->assertSee('Not set')
            ->call('openSalary', 'e1')
            ->set('salary.c1.assigned', true)
            ->set('salary.c1.amount', '60000')
            ->set('salary.c2.assigned', true)
            ->call('saveSalary')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['components'] === [['id' => 'c1', 'amount' => 60000.0], ['id' => 'c2', 'amount' => null]]);
    }

    public function test_run_inputs_compute_and_finalise(): void
    {
        $run = fn (string $status) => [
            'id' => 'r1', 'month' => '2026-10', 'status' => $status, 'computed_at' => null, 'approved_at' => null, 'finalized_at' => null, 'paid_on' => null,
            'computed_by' => null, 'approved_by' => null, 'totals' => ['gross' => 90000.0, 'deductions' => 2000.0, 'net' => 88000.0], 'by_department' => ['CSE' => 88000.0],
            'can' => ['edit' => $status === 'computed', 'approve' => false, 'finalize' => $status === 'approved'], 'payslips' => [$this->payslip()],
        ];
        Http::fake([
            self::API.'payroll-runs' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'r1']], 201) : Http::response(['data' => [['id' => 'r1', 'month' => '2026-10', 'status' => 'computed', 'paid_on' => null, 'employees' => 1, 'net_total' => 88000.0]]]),
            self::API.'payroll-runs/r1' => Http::response(['data' => $run('computed')]),
            self::API.'payroll-runs/r1/compute' => Http::response(['data' => []]),
            self::API.'payslips/ps1/inputs' => Http::response(['message' => 'ok']),
        ]);

        Livewire::test(Runs::class)->assertSee('October 2026')->set('month', '2026-11')->call('create')->assertRedirect(route('payroll-runs.show', 'r1'));

        Livewire::test(RunShow::class, ['run' => 'r1'])
            ->assertSee('₹88,000')
            ->call('openInputs', 'ps1')
            ->set('inputs.lop_days', '2')
            ->call('addAdjustment')
            ->set('inputs.adjustments.0.label', 'Exam duty honorarium')
            ->set('inputs.adjustments.0.amount', '1500')
            ->call('saveInputs')
            ->assertHasNoErrors()
            ->call('step', 'compute');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'ps1/inputs') && $r['lop_days'] === 2.0 && $r['adjustments'] === [['kind' => 'earning', 'label' => 'Exam duty honorarium', 'amount' => 1500.0]]);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'r1/compute'));
    }

    public function test_payslip_and_my_payslips(): void
    {
        Http::fake([
            self::API.'payslips/ps1' => Http::response(['data' => [...$this->payslip(), 'designation' => 'Professor', 'run' => ['id' => 'r1', 'month' => '2026-10', 'status' => 'finalized', 'paid_on' => '2026-10-31']]]),
            self::API.'me/payslips' => Http::response(['data' => [['id' => 'ps1', 'month' => '2026-10', 'paid_on' => '2026-10-31', 'gross' => 90000.0, 'deductions' => 2000.0, 'net' => 88000.0]]]),
        ]);

        Livewire::test(PayslipShow::class, ['payslip' => 'ps1'])->assertSeeInOrder(['October 2026', 'Dr. Kavya', 'Basic pay', '₹60,000.00', 'Provident fund', 'Net pay: ₹88,000.00']);
        Livewire::test(MyPayslips::class)->assertSee('October 2026')->assertSee('₹88,000');
    }
}
