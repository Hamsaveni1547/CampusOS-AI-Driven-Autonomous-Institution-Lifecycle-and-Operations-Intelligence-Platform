<?php

namespace Tests\Feature\Platform;

use App\Livewire\Platform\Institutions\Index as Institutions;
use App\Livewire\Platform\Monitoring;
use App\Livewire\Platform\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['is_platform_admin' => true, 'permissions' => []]);
    }

    /**
     * @return array<string, mixed>
     */
    private function institution(string $status = 'active'): array
    {
        return ['id' => 'i1', 'code' => 'RVCE', 'name' => 'RV College of Engineering', 'short_name' => null, 'type' => 'college', 'status' => $status, 'parent' => null, 'email' => null, 'phone' => null, 'website' => null, 'address' => ['line' => null, 'city' => 'Bengaluru', 'state' => null, 'country' => 'IN', 'postal_code' => null], 'timezone' => 'Asia/Kolkata', 'currency' => 'INR', 'affiliation' => null, 'users_count' => 1200, 'suspension_reason' => $status === 'suspended' ? 'Unpaid' : null, 'created_at' => '2026-06-01T10:00:00+05:30'];
    }

    public function test_platform_pages_are_for_platform_admins_only(): void
    {
        $this->fakeShell();

        foreach (['platform.institutions.index', 'platform.settings.index', 'platform.monitoring.index'] as $route) {
            $this->signIn(['is_platform_admin' => false, 'permissions' => ['admin.settings.manage']])->get(route($route))->assertForbidden();
        }
    }

    public function test_create_institution_with_its_first_administrator(): void
    {
        Http::fake([self::API.'platform/institutions*' => fn (Request $r) => $r->method() === 'POST'
            ? Http::response(['data' => $this->institution()], 201)
            : Http::response(['data' => [$this->institution()], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]])]);

        Livewire::test(Institutions::class)
            ->assertSee('RV College of Engineering')
            ->assertSee('1,200')
            ->call('openCreate')
            ->set('form.code', 'BMSCE')->set('form.name', 'BMS College of Engineering')
            ->call('save')
            ->assertHasErrors(['admin.name', 'admin.email'])
            ->set('admin.name', 'Meera Iyer')->set('admin.email', 'meera@bmsce.edu')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['code'] === 'BMSCE' && $r['admin'] === ['name' => 'Meera Iyer', 'email' => 'meera@bmsce.edu', 'mobile' => null] && ! array_key_exists('parent_id', $r->data()));
    }

    public function test_suspend_needs_reason_and_reactivate(): void
    {
        Http::fake([
            self::API.'platform/institutions/i1/suspend' => Http::response(['data' => $this->institution('suspended')]),
            self::API.'platform/institutions/i1/reactivate' => Http::response(['data' => $this->institution()]),
            self::API.'platform/institutions*' => Http::response(['data' => [$this->institution('suspended')], 'meta' => ['total' => 1]]),
        ]);

        Livewire::test(Institutions::class)
            ->assertSee('Unpaid')
            ->call('askSuspend', 'i1')
            ->call('suspend')
            ->assertHasErrors(['reason'])
            ->set('reason', 'Contract ended')
            ->call('suspend')
            ->assertHasNoErrors()
            ->call('reactivate', 'i1');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'i1/suspend') && $r['reason'] === 'Contract ended');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'i1/reactivate'));
    }

    public function test_platform_settings_use_platform_endpoints(): void
    {
        $group = ['group' => 'general', 'label' => 'General', 'settings' => [['key' => 'support_email', 'label' => 'Support email', 'type' => 'string', 'default' => '', 'value' => '', 'secret' => false]]];
        Http::fake([
            self::API.'platform/settings/general' => Http::response(['data' => $group]),
            self::API.'platform/settings' => Http::response(['data' => [$group]]),
        ]);

        Livewire::test(Settings::class)
            ->assertSee('Platform settings')
            ->assertSee('Save settings')
            ->set('values.support_email', 'help@campusos.in')
            ->call('save');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), 'platform/settings/general') && $r['support_email'] === 'help@campusos.in');
    }

    public function test_monitoring_shows_queues_and_retries_failed_jobs(): void
    {
        Http::fake([
            self::API.'platform/monitoring/health' => Http::response(['data' => ['status' => 'ok', 'checks' => ['database' => ['status' => 'ok', 'message' => null, 'latency_ms' => 0.8]]]]),
            self::API.'platform/monitoring/queues' => Http::response(['data' => [['queue' => 'imports', 'pending' => 12]]]),
            self::API.'platform/monitoring/failed-jobs/job-1/retry' => Http::response(['message' => 'Queued']),
            self::API.'platform/monitoring/failed-jobs*' => Http::response(['data' => [['id' => 'job-1', 'queue' => 'notifications', 'job' => 'App\\Jobs\\SendMail', 'exception' => 'Swift_TransportException: timeout', 'failed_at' => '2026-10-26 09:00:00']]]),
        ]);

        Livewire::test(Monitoring::class)
            ->assertSee('Queue: imports')
            ->assertSee('SendMail')
            ->assertSee('Swift_TransportException')
            ->call('retry', 'job-1');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'failed-jobs/job-1/retry'));
    }
}
