<?php

namespace Tests\Feature\Assets;

use App\Livewire\Assets\Bookings;
use App\Livewire\Assets\Maintenance;
use App\Livewire\Assets\Register;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AssetScreensTest extends TestCase
{
    private const META = ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['assets.manage', 'assets.maintenance.manage', 'assets.reports.view', 'assets.rooms.book'], 'roles' => ['admin_staff', 'staff']]);
        Http::fake([
            self::API.'rooms?*' => Http::response(['data' => [['id' => 'r1', 'code' => 'CS-LAB1', 'name' => 'Programming Lab']], 'meta' => self::META]),
            self::API.'departments*' => Http::response(['data' => []]),
        ]);
    }

    public function test_register_lists_and_adds_assets(): void
    {
        Http::fake([
            self::API.'assets/summary' => Http::response(['data' => ['total' => 1, 'value' => 52000.0, 'by_status' => ['working' => 1], 'by_category' => ['Computers' => 1], 'warranty_expiring' => 1, 'open_requests' => 0, 'categories' => ['Computers']]]),
            self::API.'assets?*' => Http::response(['data' => [['id' => 'a1', 'tag' => 'BIT-PC-001', 'name' => 'Desktop computer', 'category' => 'Computers', 'department' => null, 'room' => ['id' => 'r1', 'code' => 'CS-LAB1', 'name' => 'Lab'], 'make' => 'Dell', 'model' => 'OptiPlex', 'serial_no' => null, 'purchased_on' => null, 'cost' => 52000.0, 'vendor' => null, 'warranty_until' => now()->addDays(20)->toDateString(), 'status' => 'working']], 'meta' => self::META]),
            self::API.'assets' => Http::response(['data' => ['id' => 'a2']], 201),
        ]);

        Livewire::test(Register::class)
            ->assertSee('BIT-PC-001')
            ->assertSee('₹52,000')
            ->call('open')
            ->set('form.tag', 'BIT-PRJ-01')
            ->set('form.name', 'Projector')
            ->set('form.category', 'Projectors')
            ->set('form.room_id', 'r1')
            ->set('form.cost', '38000')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'api/v1/assets') && $r['tag'] === 'BIT-PRJ-01' && $r['cost'] === 38000.0 && $r['vendor'] === null);
    }

    public function test_fault_is_reported_and_assigned(): void
    {
        $row = ['id' => 'm1', 'title' => 'PC does not boot', 'description' => null, 'priority' => 'high', 'status' => 'open', 'asset' => ['id' => 'a1', 'tag' => 'BIT-PC-001', 'name' => 'Desktop'], 'room' => ['code' => 'CS-LAB1', 'name' => 'Lab'], 'assigned_to' => null, 'resolution' => null, 'cost' => null, 'raised_by' => 'Dr. Kavya', 'raised_at' => now()->toIso8601String(), 'resolved_at' => null];
        Http::fake([
            self::API.'maintenance-requests?*' => Http::response(['data' => [$row], 'meta' => self::META]),
            self::API.'maintenance-requests' => Http::response(['data' => ['id' => 'm2']], 201),
            self::API.'maintenance-requests/m1' => Http::response(['data' => [...$row, 'status' => 'in_progress']]),
        ]);

        Livewire::test(Maintenance::class)
            ->assertSee('PC does not boot')
            ->call('open')
            ->set('form.title', 'Projector flickers')
            ->set('form.room_id', 'r1')
            ->call('raise')
            ->assertHasNoErrors()
            ->call('openUpdate', 'm1', 'in_progress', $row)
            ->set('update.assigned_to', 'Ravi (IT)')
            ->call('saveUpdate');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'maintenance-requests') && $r['room_id'] === 'r1' && $r['asset'] === null);
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['status'] === 'in_progress' && $r['assigned_to'] === 'Ravi (IT)');
    }

    public function test_room_is_booked(): void
    {
        Http::fake([
            self::API.'room-bookings?*' => Http::response(['data' => [], 'meta' => self::META]),
            self::API.'room-bookings' => Http::response(['data' => ['id' => 'b1']], 201),
        ]);

        Livewire::test(Bookings::class)->assertSee('No upcoming bookings')->call('open')->set('form.room_id', 'r1')->set('form.purpose', 'Project review')->call('book')->assertHasNoErrors();
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'room-bookings') && $r['room_id'] === 'r1' && $r['starts_at'] === '14:00');
    }
}
