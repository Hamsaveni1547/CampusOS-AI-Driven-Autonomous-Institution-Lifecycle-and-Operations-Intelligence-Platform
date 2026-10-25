<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Roles\Index as Roles;
use App\Livewire\Admin\Users\Index as Users;
use App\Livewire\Admin\Users\Show as UserShow;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class UsersAndRolesTest extends TestCase
{
    private const ADMIN = ['identity.users.view', 'identity.users.create', 'identity.users.update', 'identity.users.deactivate', 'identity.users.unlock', 'identity.users.reset_password', 'identity.role_assignments.manage', 'identity.security_events.view', 'identity.roles.view', 'identity.roles.manage'];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => self::ADMIN, 'roles' => ['institution_admin']]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function user(array $overrides = []): array
    {
        return array_replace([
            'id' => 'u1', 'name' => 'Priya Sharma', 'email' => 'priya@example.edu', 'email_verified' => true, 'mobile' => null, 'username' => null,
            'status' => 'active', 'membership_status' => 'active', 'is_locked' => false, 'mfa_enabled' => true, 'last_login_at' => null, 'created_at' => '2026-06-01T10:00:00+05:30',
            'role_assignments' => [['id' => 'ra1', 'status' => 'active', 'valid_until' => null, 'role' => ['id' => 'r1', 'display_name' => 'Faculty'], 'department' => ['id' => 'd1', 'name' => 'Computer Science']]],
        ], $overrides);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function roles(): array
    {
        return [
            ['id' => 'r1', 'name' => 'faculty', 'display_name' => 'Faculty', 'description' => null, 'is_system' => true, 'is_active' => true, 'is_department_scoped' => false, 'permissions' => ['hr.employees.view'], 'active_assignments_count' => 12],
            ['id' => 'r9', 'name' => 'exam_cell', 'display_name' => 'Exam cell', 'description' => 'Runs exams', 'is_system' => false, 'is_active' => true, 'is_department_scoped' => false, 'permissions' => [], 'active_assignments_count' => 0],
        ];
    }

    private function fakeLookups(): void
    {
        Http::fake([
            self::API.'roles*' => Http::response(['data' => $this->roles()]),
            self::API.'departments*' => Http::response(['data' => [['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science']]]),
        ]);
    }

    public function test_users_page_needs_permission(): void
    {
        $this->fakeShell();

        $this->signIn(['permissions' => []])->get(route('users.index'))->assertForbidden();
    }

    public function test_users_list_sends_filters_and_shows_people(): void
    {
        Http::fake([self::API.'users*' => Http::response(['data' => [$this->user(['is_locked' => true])], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]])]);
        $this->fakeLookups();

        Livewire::test(Users::class)
            ->assertSee('Priya Sharma')
            ->assertSee('Faculty')
            ->set('filters.locked', '1')
            ->set('search', 'priya')
            ->assertSee('Locked: Yes');

        Http::assertSent(fn (Request $r) => str_contains(urldecode($r->url()), 'filter[locked]=1') && str_contains(urldecode($r->url()), 'filter[search]=priya'));
    }

    public function test_creating_user_without_invite_shows_temporary_password_once(): void
    {
        Http::fake([
            self::API.'users' => fn (Request $r) => $r->method() === 'POST'
                ? Http::response(['data' => $this->user(), 'meta' => ['temporary_password' => 'Tmp#4821xy']], 201)
                : Http::response(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 0]]),
            self::API.'users*' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 0]]),
        ]);
        $this->fakeLookups();

        Livewire::test(Users::class)
            ->call('openCreate')
            ->set('form.name', 'Priya Sharma')->set('form.email', 'priya@example.edu')->set('form.roles', ['r1'])->set('form.send_invite', false)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('temporaryPassword', 'Tmp#4821xy')
            ->assertDispatched('open-dialog', 'temporary-password');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['roles'] === ['r1'] && $r['send_invite'] === false && ! array_key_exists('mobile', $r->data()));
    }

    public function test_api_validation_errors_land_on_fields(): void
    {
        Http::fake([
            self::API.'users' => fn (Request $r) => $r->method() === 'POST'
                ? Http::response($this->apiError('Invalid', 'VALIDATION_FAILED', ['errors' => ['email' => ['This email is already a member.']]]), 422)
                : Http::response(['data' => [], 'meta' => ['total' => 0]]),
            self::API.'users*' => Http::response(['data' => [], 'meta' => ['total' => 0]]),
        ]);
        $this->fakeLookups();

        Livewire::test(Users::class)
            ->set('form.name', 'Priya')->set('form.email', 'priya@example.edu')
            ->call('create')
            ->assertHasErrors(['form.email']);
    }

    public function test_user_page_shows_roles_and_activity(): void
    {
        Http::fake([
            self::API.'users/u1/security-events*' => Http::response(['data' => [['id' => 'e1', 'event' => 'login_succeeded', 'severity' => 'info', 'ip_address' => '10.0.0.4', 'actor' => null, 'created_at' => now()->toIso8601String()]], 'meta' => ['next_cursor' => null]]),
            self::API.'users/u1' => Http::response(['data' => $this->user()]),
        ]);
        $this->fakeLookups();

        Livewire::test(UserShow::class, ['user' => 'u1'])
            ->assertSee('Priya Sharma')
            ->assertSee('Computer Science')
            ->assertSee('Login succeeded')
            ->assertSee('10.0.0.4');
    }

    public function test_assigning_and_revoking_roles(): void
    {
        Http::fake([
            self::API.'users/u1/security-events*' => Http::response(['data' => [], 'meta' => ['next_cursor' => null]]),
            self::API.'users/u1/role-assignments' => Http::response(['data' => ['id' => 'ra2']], 201),
            self::API.'role-assignments/ra1' => Http::response(['message' => 'Revoked']),
            self::API.'users/u1' => Http::response(['data' => $this->user()]),
        ]);
        $this->fakeLookups();

        Livewire::test(UserShow::class, ['user' => 'u1'])
            ->set('assign.role_id', 'r1')->set('assign.department_id', 'd1')->set('assign.valid_until', '2027-03-31')
            ->call('assignRole')
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog', 'assign-role')
            ->call('askRevoke', 'ra1')
            ->set('reason', 'Moved department')
            ->call('revoke');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'users/u1/role-assignments') && $r['department_id'] === 'd1' && $r['valid_until'] === '2027-03-31');
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), 'role-assignments/ra1') && $r['reason'] === 'Moved department');
    }

    public function test_deactivation_needs_a_reason_and_reset_password_can_show_temporary_password(): void
    {
        Http::fake([
            self::API.'users/u1/security-events*' => Http::response(['data' => [], 'meta' => ['next_cursor' => null]]),
            self::API.'users/u1/deactivate' => Http::response(['message' => 'Deactivated']),
            self::API.'users/u1/reset-password' => Http::response(['data' => ['temporary_password' => 'Tmp#0001ab'], 'message' => 'Done']),
            self::API.'users/u1' => Http::response(['data' => $this->user()]),
        ]);
        $this->fakeLookups();

        Livewire::test(UserShow::class, ['user' => 'u1'])
            ->call('deactivate')
            ->assertHasErrors(['reason'])
            ->set('reason', 'Left the institution')
            ->call('deactivate')
            ->assertHasNoErrors()
            ->call('resetPassword', 'temporary_password')
            ->assertSet('temporaryPassword', 'Tmp#0001ab');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'deactivate') && $r['reason'] === 'Left the institution');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'reset-password') && $r['mode'] === 'temporary_password');
    }

    public function test_roles_built_in_are_read_only_and_custom_roles_save_permissions(): void
    {
        Http::fake([
            self::API.'permissions' => Http::response(['data' => [['module' => 'hr', 'permissions' => [
                ['name' => 'hr.employees.view', 'description' => 'View staff', 'is_sensitive' => false],
                ['name' => 'hr.employees.manage', 'description' => 'Manage staff', 'is_sensitive' => true],
            ]]]]),
            self::API.'roles/r1' => Http::response(['data' => $this->roles()[0]]),
            self::API.'roles/r9' => fn (Request $r) => Http::response(['data' => $this->roles()[1]]),
            self::API.'roles*' => Http::response(['data' => $this->roles()]),
        ]);

        $component = Livewire::test(Roles::class)
            ->assertSee('Exam cell')
            ->assertSee('Built-in')
            ->call('open', 'r1')
            ->assertSet('readOnly', true)
            ->call('save');

        Http::assertNotSent(fn (Request $r) => $r->method() === 'PATCH');

        $component->call('open', 'r9')
            ->assertSet('readOnly', false)
            ->call('toggleModule', ['hr.employees.view', 'hr.employees.manage'], true)
            ->assertSet('form.permissions', ['hr.employees.view', 'hr.employees.manage'])
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && str_ends_with($r->url(), 'roles/r9') && $r['permissions'] === ['hr.employees.view', 'hr.employees.manage']);
    }
}
