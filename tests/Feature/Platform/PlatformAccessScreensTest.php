<?php

namespace Tests\Feature\Platform;

use App\Livewire\Platform\Access\Modules;
use App\Livewire\Platform\Access\Permissions;
use App\Livewire\Platform\Access\Roles;
use App\Livewire\Platform\Access\RoleShow;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAccessScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['is_platform_admin' => true, 'permissions' => []]);

        $role = ['id' => 'r1', 'name' => 'hr_officer', 'display_name' => 'HR officer', 'description' => 'Staff records', 'is_department_scoped' => false, 'is_customised' => false];
        Http::fake([
            self::API.'platform/access/modules' => Http::response(['data' => [
                ['key' => 'identity', 'label' => 'Users & access', 'description' => 'Sign-in', 'core' => true, 'permissions' => 10, 'sensitive' => 2],
                ['key' => 'payroll', 'label' => 'Payroll', 'description' => 'Salaries', 'core' => false, 'permissions' => 4, 'sensitive' => 1],
            ]]),
            self::API.'platform/access/permissions' => Http::response(['data' => [
                ['module' => 'payroll', 'label' => 'Payroll', 'permissions' => [
                    ['name' => 'payroll.reports.view', 'description' => 'View payroll', 'sensitive' => true, 'roles' => ['hr_officer']],
                    ['name' => 'payroll.runs.approve', 'description' => 'Approve payroll runs', 'sensitive' => true, 'roles' => []],
                ]],
            ]]),
            self::API.'platform/access/roles/r1/permissions' => Http::response(['message' => 'Permissions saved.']),
            self::API.'platform/access/roles/r1/reset' => Http::response(['message' => 'Reset.']),
            self::API.'platform/access/roles/r1' => fn (Request $r) => $r->method() === 'PATCH' ? Http::response(['message' => 'ok']) : Http::response(['data' => [...$role, 'permissions' => ['payroll.reports.view'], 'defaults' => ['payroll.reports.view']]]),
            self::API.'platform/access/roles' => Http::response(['data' => [[...$role, 'users' => 3, 'permissions_count' => 1]]]),
            self::API.'platform/institutions/i1/modules' => fn (Request $r) => $r->method() === 'PUT' ? Http::response(['message' => 'ok']) : Http::response(['data' => [['key' => 'identity', 'label' => 'Users & access', 'core' => true, 'enabled' => true], ['key' => 'payroll', 'label' => 'Payroll', 'core' => false, 'enabled' => true]]]),
            self::API.'platform/institutions*' => Http::response(['data' => [['id' => 'i1', 'name' => 'Bangalore Institute of Technology']], 'meta' => ['total' => 1]]),
        ]);
    }

    public function test_modules_are_switched_per_institution(): void
    {
        Livewire::test(Modules::class)
            ->assertSee('Payroll')->assertSee('Core')
            ->set('institution', 'i1')
            ->assertSet('enabled.payroll', true)
            ->set('enabled.payroll', false)
            ->call('save');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['modules'] === ['identity' => true, 'payroll' => false]);
    }

    public function test_permission_catalogue_and_role_matrix(): void
    {
        Livewire::test(Permissions::class)->assertSee('payroll.runs.approve')->set('search', 'approve')->assertDontSee('payroll.reports.view');
        Livewire::test(Roles::class)->assertSee('HR officer');

        Livewire::test(RoleShow::class, ['role' => 'r1'])
            ->assertSet('granted', ['payroll.reports.view'])
            ->call('toggleModule', 'payroll', true)
            ->assertSet('granted', ['payroll.reports.view', 'payroll.runs.approve'])
            ->call('save')
            ->assertHasNoErrors()
            ->call('resetDefaults');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), 'r1/permissions') && $r['permissions'] === ['payroll.reports.view', 'payroll.runs.approve']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'r1/reset'));
    }

    public function test_access_pages_are_for_platform_admins_only(): void
    {
        foreach (['platform.access.modules', 'platform.access.permissions', 'platform.access.roles'] as $route) {
            $this->signIn(['is_platform_admin' => false, 'permissions' => ['identity.roles.view']])->get(route($route))->assertForbidden();
        }
    }
}
