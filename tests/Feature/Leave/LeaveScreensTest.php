<?php

namespace Tests\Feature\Leave;

use App\Livewire\Leave\Approvals;
use App\Livewire\Leave\MyLeave;
use App\Livewire\Leave\Setup;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['leave.manage', 'leave.approve', 'leave.reports.view'], 'roles' => ['hod', 'faculty']]);
    }

    public function test_staff_sees_balances_and_applies(): void
    {
        $type = ['id' => 't1', 'code' => 'CL', 'name' => 'Casual leave', 'days_per_year' => 12.0, 'is_paid' => true, 'allow_half_day' => true, 'status' => 'active'];
        Http::fake([
            self::API.'me/leave' => Http::response(['data' => ['year' => 2026, 'balances' => [['type' => $type, 'allotted' => 12.0, 'used' => 2.0, 'pending' => 1.0, 'available' => 9.0]], 'applications' => [
                ['id' => 'a1', 'employee' => [], 'type' => ['code' => 'CL', 'name' => 'Casual leave', 'is_paid' => true], 'from_date' => '2026-11-20', 'to_date' => '2026-11-20', 'half_day' => false, 'days' => 1.0, 'reason' => 'Bank work', 'status' => 'pending', 'remarks' => null, 'decided_by' => null, 'applied_at' => null, 'can_cancel' => true],
            ]]]),
            self::API.'me/leave-applications' => Http::response(['data' => ['id' => 'a2', 'days' => 3.0]], 201),
            self::API.'me/leave-applications/a1/cancel' => Http::response(['message' => 'ok']),
        ]);

        Livewire::test(MyLeave::class)
            ->assertSeeInOrder(['Casual leave', '9', '/ 12', 'Bank work'])
            ->call('open')
            ->set('form.leave_type_id', 't1')
            ->set('form.from_date', '2026-11-23')
            ->set('form.to_date', '2026-11-25')
            ->set('form.reason', 'Family function')
            ->call('apply')
            ->assertHasNoErrors()
            ->call('cancel', 'a1');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'me/leave-applications') && $r['leave_type_id'] === 't1' && $r['half_day'] === false);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'a1/cancel'));
    }

    public function test_approver_decides_and_admin_allots(): void
    {
        Http::fake([
            self::API.'leave-applications?*' => Http::response(['data' => [['id' => 'a1', 'employee' => ['id' => 'e1', 'name' => 'Dr. Kavya', 'code' => 'E1', 'department' => 'CSE'], 'type' => ['code' => 'CL', 'name' => 'Casual leave', 'is_paid' => true], 'from_date' => '2026-11-20', 'to_date' => '2026-11-21', 'half_day' => false, 'days' => 2.0, 'reason' => 'Wedding', 'status' => 'pending', 'remarks' => null, 'decided_by' => null, 'applied_at' => null, 'can_decide' => true]], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]]),
            self::API.'leave-applications/a1/decide' => Http::response(['message' => 'ok']),
            self::API.'leave-types' => Http::response(['data' => []]),
            self::API.'leave-balances/allot' => Http::response(['data' => ['created' => 40, 'message' => '40 balances created for 2026.']]),
        ]);

        Livewire::test(Approvals::class)->assertSee('Dr. Kavya')->assertSee('Wedding')->call('askReject', 'a1')->call('reject')->assertHasErrors('remarks')->set('remarks', 'Exam week')->call('reject')->assertHasNoErrors();
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'a1/decide') && $r['approve'] === false && $r['remarks'] === 'Exam week');

        Livewire::test(Setup::class)->assertSee('No leave types yet')->set('year', 2026)->call('allot');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'leave-balances/allot') && $r['year'] === 2026);
    }
}
