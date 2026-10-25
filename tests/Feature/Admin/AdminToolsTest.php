<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Audit\Index as Audit;
use App\Livewire\Admin\Health\Index as Health;
use App\Livewire\Admin\Imports\Index as Imports;
use App\Livewire\Admin\Institution\Edit as InstitutionEdit;
use App\Livewire\Admin\Settings\Index as Settings;
use App\Livewire\Admin\Templates\Index as Templates;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AdminToolsTest extends TestCase
{
    private const ADMIN = ['institution.profile.manage', 'admin.settings.view', 'admin.settings.manage', 'communication.templates.manage', 'communication.deliveries.view', 'admin.audit.view', 'admin.audit.export', 'identity.security_events.view', 'identity.users.import', 'hr.employees.import', 'admin.monitoring.view'];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => self::ADMIN]);
    }

    /**
     * @return array<string, mixed>
     */
    private function institution(): array
    {
        return [
            'id' => 'i1', 'code' => 'RVCE', 'name' => 'RV College of Engineering', 'short_name' => 'RVCE', 'type' => 'college', 'status' => 'active',
            'email' => null, 'phone' => null, 'website' => null, 'address' => ['line' => null, 'city' => 'Bengaluru', 'state' => 'Karnataka', 'country' => 'IN', 'postal_code' => '560059'],
            'timezone' => 'Asia/Kolkata', 'currency' => 'INR', 'affiliation' => 'VTU', 'accreditation_codes' => null, 'established_year' => 1963, 'logo_file_id' => null,
        ];
    }

    public function test_institution_profile_saves_cleaned_values(): void
    {
        Http::fake([self::API.'institution' => fn (Request $r) => Http::response(['data' => $this->institution()])]);

        Livewire::test(InstitutionEdit::class)
            ->assertSet('form.city', 'Bengaluru')
            ->set('form.country', 'in')->set('form.email', '')->set('form.website', 'https://rvce.edu.in')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['country'] === 'IN' && $r['email'] === null && $r['established_year'] === 1963 && $r['website'] === 'https://rvce.edu.in');
    }

    public function test_settings_fields_follow_their_types_and_secrets_are_kept(): void
    {
        $group = ['group' => 'security', 'label' => 'Security', 'settings' => [
            ['key' => 'mfa_required', 'label' => 'Require two-step', 'type' => 'bool', 'default' => false, 'value' => false, 'secret' => false],
            ['key' => 'lockout_attempts', 'label' => 'Attempts', 'type' => 'int', 'default' => 5, 'value' => 5, 'secret' => false],
            ['key' => 'mfa_roles', 'label' => 'Roles', 'type' => 'array', 'default' => [], 'value' => ['institution_admin'], 'secret' => false],
            ['key' => 'smtp_password', 'label' => 'SMTP password', 'type' => 'string', 'default' => null, 'value' => '********', 'secret' => true],
        ]];
        Http::fake([
            self::API.'settings/security' => Http::response(['data' => $group]),
            self::API.'settings' => Http::response(['data' => [$group, ['group' => 'general', 'label' => 'General', 'settings' => []]]]),
        ]);

        Livewire::test(Settings::class)
            ->assertSet('group', 'security')
            ->assertSet('values.mfa_roles', 'institution_admin')
            ->assertSet('values.smtp_password', '')
            ->set('values.mfa_required', true)->set('values.lockout_attempts', '7')->set('values.mfa_roles', 'institution_admin, hod')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r->data() === ['mfa_required' => true, 'lockout_attempts' => 7, 'mfa_roles' => ['institution_admin', 'hod']]);
    }

    public function test_settings_are_read_only_without_manage_permission(): void
    {
        Http::fake([self::API.'settings' => Http::response(['data' => [['group' => 'general', 'label' => 'General', 'settings' => []]]])]);

        $this->signIn(['permissions' => ['admin.settings.view']]);

        Livewire::test(Settings::class)->assertDontSee('Save settings');
    }

    public function test_template_edit_save_and_unknown_placeholder_error(): void
    {
        $template = ['key' => 'account_setup', 'category' => 'account', 'description' => 'Account invitation', 'placeholders' => ['name', 'link'], 'channels' => [
            ['channel' => 'mail', 'default' => ['subject' => 'Welcome', 'body' => 'Hello {name}'], 'custom' => null],
        ]];
        Http::fake([
            self::API.'notification-templates/account_setup/mail' => Http::sequence()
                ->push(['data' => $template])
                ->push($this->apiError('Unknown placeholders: fee.', 'UNKNOWN_PLACEHOLDERS'), 422),
            self::API.'notification-templates/account_setup' => Http::response(['data' => $template]),
            self::API.'notification-templates' => Http::response(['data' => [$template]]),
        ]);

        Livewire::test(Templates::class)
            ->assertSee('Account invitation')
            ->call('edit', 'account_setup', 'mail')
            ->assertSet('form.subject', 'Welcome')
            ->assertSet('placeholders', ['name', 'link'])
            ->set('form.body', 'Hi {name}, open {link}')
            ->call('save')
            ->assertDispatched('close-dialog', 'template-form')
            ->set('form.body', 'Pay {fee}')
            ->call('save')
            ->assertSet('formError', 'Unknown placeholders: fee.');
    }

    public function test_audit_filters_widen_dates_and_export_streams_csv(): void
    {
        Http::fake([
            self::API.'audit/activities/export*' => Http::response("id,description\n1,Department created\n", 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="audit-2026.csv"']),
            self::API.'audit/activities*' => Http::response(['data' => [['id' => 1, 'log' => 'default', 'event' => 'created', 'description' => 'Department created', 'subject' => ['type' => 'department', 'id' => 4], 'causer' => ['id' => 'u1', 'name' => 'Anita Rao'], 'changes' => null, 'properties' => [], 'ip_address' => '10.0.0.4', 'trace_id' => null, 'created_at' => now()->toIso8601String()]], 'meta' => ['next_cursor' => 'abc']]),
        ]);

        Livewire::test(Audit::class)
            ->assertSee('Department created')
            ->assertSee('Anita Rao')
            ->set('filters.from', '2026-10-01')->set('filters.to', '2026-10-31')
            ->call('nextPage', 'abc')
            ->assertSet('previousCursors', [null]);

        Http::assertSent(fn (Request $r) => str_contains(urldecode($r->url()), 'filter[from]=2026-10-01 00:00:00') && str_contains(urldecode($r->url()), 'filter[to]=2026-10-31 23:59:59'));

        $this->fakeShell();
        $this->get(route('audit.export', ['filter' => ['event' => 'created']]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="audit-2026.csv"')
            ->assertSee('Department created');
    }

    public function test_audit_export_needs_permission(): void
    {
        $this->fakeShell();

        $this->signIn(['permissions' => ['admin.audit.view']])->get(route('audit.export'))->assertForbidden();
    }

    public function test_import_uploads_checks_and_commits(): void
    {
        $validated = ['id' => 'im1', 'type' => 'users', 'status' => 'validated', 'options' => [], 'total_rows' => 3, 'valid_rows' => 3, 'processed_rows' => 0, 'failed_rows' => 0, 'errors' => [], 'failure_message' => null, 'created_by' => null, 'created_at' => now()->toIso8601String()];
        Http::fake([
            self::API.'files' => Http::response(['data' => ['id' => '01JBXFILE00000000000000001']], 201),
            self::API.'imports/templates/users' => Http::response(['data' => ['required' => ['name', 'email'], 'optional' => ['roles']]]),
            self::API.'imports/im1/commit' => Http::response(['data' => ['id' => 'im1', 'status' => 'processing']]),
            self::API.'imports/im1' => Http::response(['data' => $validated]),
            self::API.'imports' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $validated], 202) : Http::response(['data' => [], 'meta' => ['total' => 0]]),
            self::API.'imports*' => Http::response(['data' => [], 'meta' => ['total' => 0]]),
        ]);

        Livewire::test(Imports::class, ['type' => 'users'])
            ->assertSee('name, email')
            ->set('file', UploadedFile::fake()->createWithContent('users.csv', "name,email\nA,a@x.edu\n"))
            ->set('sendInvites', false)
            ->call('start')
            ->assertHasNoErrors()
            ->assertSet('openId', 'im1')
            ->assertSee('Commit 3 rows')
            ->call('commit');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/imports') && $r['type'] === 'users' && $r['options'] === ['send_invite' => false]);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'imports/im1/commit'));
    }

    public function test_import_type_must_be_allowed(): void
    {
        Http::fake([self::API.'imports*' => Http::response(['data' => [], 'meta' => ['total' => 0]])]);
        $this->signIn(['permissions' => ['identity.users.import']]);

        Livewire::test(Imports::class)
            ->set('type', 'employees')
            ->set('file', UploadedFile::fake()->createWithContent('staff.csv', "name\n"))
            ->call('start')
            ->assertHasErrors(['type']);
    }

    public function test_health_shows_each_check(): void
    {
        Http::fake([self::API.'admin/health' => Http::response(['data' => ['status' => 'degraded', 'checks' => [
            'database' => ['status' => 'ok', 'message' => null, 'latency_ms' => 1.2],
            'queue' => ['status' => 'fail', 'message' => 'RuntimeException: connection refused', 'latency_ms' => null],
        ]]])]);

        Livewire::test(Health::class)
            ->assertSee('Degraded')
            ->assertSee('Database')
            ->assertSee('connection refused');
    }
}
