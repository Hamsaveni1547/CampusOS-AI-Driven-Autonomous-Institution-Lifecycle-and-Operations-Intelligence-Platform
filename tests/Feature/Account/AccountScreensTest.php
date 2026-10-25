<?php

namespace Tests\Feature\Account;

use App\Livewire\Account\NotificationPreferences;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Security;
use App\Livewire\Account\StaffProfile;
use App\Livewire\Calendar;
use App\Livewire\NotificationBell;
use App\Livewire\Notifications;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AccountScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn();
    }

    /**
     * @return array<string, mixed>
     */
    private function notification(string $id, bool $read = false): array
    {
        return ['id' => $id, 'category' => 'security', 'title' => "Title {$id}", 'body' => 'New sign-in from Chrome on Windows.', 'action_url' => null, 'action_text' => null, 'read_at' => $read ? now()->toIso8601String() : null, 'created_at' => now()->subHour()->toIso8601String()];
    }

    public function test_home_shows_term_notifications_and_events(): void
    {
        $this->fakeShell([
            self::API.'me/notifications*' => Http::response(['data' => [$this->notification('n1')], 'meta' => ['next_cursor' => null]]),
            self::API.'calendar-events*' => Http::response(['data' => [['id' => 'e1', 'title' => 'Deepavali', 'type' => 'holiday', 'starts_on' => now()->addDays(3)->toDateString(), 'ends_on' => now()->addDays(3)->toDateString(), 'is_holiday' => true, 'audience' => 'all', 'description' => null, 'academic_year' => null]]]),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Odd Semester 2026')
            ->assertSee('Title n1')
            ->assertSee('Deepavali');
    }

    public function test_home_asks_to_turn_on_two_step_when_off(): void
    {
        $this->fakeShell();

        $this->signIn(['mfa' => ['enabled' => false]])->get('/')->assertSee('Protect your account with two-step verification');
    }

    public function test_bell_shows_unread_count_and_survives_api_errors(): void
    {
        Http::fake([self::API.'me/notifications/unread-count' => Http::sequence()
            ->push(['data' => ['unread' => 4]])
            ->push($this->apiError('Down', 'SERVER_ERROR'), 500)]);

        Livewire::test(NotificationBell::class)
            ->assertSet('unread', 4)
            ->assertSee('4')
            ->call('refreshCount')
            ->assertSet('unread', 4);
    }

    public function test_notifications_list_filters_and_marks_read(): void
    {
        Http::fake([
            self::API.'me/notifications/n1/read' => Http::response(['message' => 'ok']),
            self::API.'me/notifications/read-all' => Http::response(['message' => 'ok']),
            self::API.'me/notifications*' => Http::response(['data' => [$this->notification('n1'), $this->notification('n2', true)], 'meta' => ['next_cursor' => 'c2']]),
        ]);

        Livewire::test(Notifications::class)
            ->assertSee('Title n1')
            ->assertSee('Load more')
            ->call('markRead', 'n1')
            ->assertDispatched('notifications-changed')
            ->call('markAllRead')
            ->assertDispatched('toast')
            ->set('unreadOnly', true)
            ->assertSet('cursors', ['']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'filter%5Bunread%5D=1'));
    }

    public function test_calendar_shows_month_and_navigates(): void
    {
        Http::fake([self::API.'calendar-events*' => Http::response(['data' => [
            ['id' => 'e1', 'title' => 'Mid-term exams', 'type' => 'examination', 'starts_on' => '2026-11-09', 'ends_on' => '2026-11-11', 'is_holiday' => false, 'audience' => 'students', 'description' => null, 'academic_year' => null],
        ]])]);

        Livewire::withQueryParams(['month' => '2026-11'])->test(Calendar::class)
            ->assertSee('November 2026')
            ->assertSee('Mid-term exams')
            ->call('next')->assertSet('month', '2026-12')
            ->call('previous')->call('previous')->assertSet('month', '2026-10');

        Http::assertSent(fn (Request $request) => str_contains(urldecode($request->url()), 'filter[from]=2026-11-01') && str_contains(urldecode($request->url()), 'filter[to]=2026-11-30'));
    }

    public function test_profile_update(): void
    {
        Http::fake([self::API.'me' => Http::sequence()
            ->push(['data' => $this->me()])
            ->push(['data' => $this->me(['name' => 'Dr. Anita Rao', 'locale' => 'kn'])])]);

        Livewire::test(Profile::class)
            ->assertSet('name', 'Anita Rao')
            ->set('name', 'Dr. Anita Rao')
            ->set('locale', 'kn')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame('Dr. Anita Rao', session('campus.me.name'));
        Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && $request['name'] === 'Dr. Anita Rao' && $request['locale'] === 'kn');
    }

    public function test_security_page_password_change_and_sessions(): void
    {
        Http::fake([
            self::API.'me/mfa' => Http::response(['data' => ['enabled' => true, 'required' => false, 'recovery_codes_remaining' => 8, 'methods' => [
                ['id' => 'm1', 'type' => 'totp', 'is_primary' => true, 'is_confirmed' => true, 'destination' => null, 'confirmed_at' => now()->toIso8601String(), 'last_used_at' => null],
            ]]]),
            self::API.'me/sessions' => Http::response(['data' => [
                ['id' => '1', 'device_name' => 'Chrome on Windows', 'ip_address' => '10.0.0.1', 'last_used_ip' => '10.0.0.1', 'user_agent' => null, 'is_current' => true, 'last_used_at' => now()->toIso8601String(), 'expires_at' => null, 'created_at' => now()->toIso8601String()],
                ['id' => '2', 'device_name' => 'Safari on iOS', 'ip_address' => '10.0.0.2', 'last_used_ip' => null, 'user_agent' => null, 'is_current' => false, 'last_used_at' => null, 'expires_at' => null, 'created_at' => now()->toIso8601String()],
            ]]),
            self::API.'me/sessions/2' => Http::response(['message' => 'ok']),
            self::API.'me/trusted-devices' => Http::response(['data' => []]),
            self::API.'me/security-events*' => Http::response(['data' => [['id' => 'ev1', 'event' => 'login_succeeded', 'severity' => 'info', 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/130.0', 'created_at' => now()->toIso8601String()]], 'meta' => []]),
            self::API.'me/password' => Http::sequence()
                ->push($this->apiError('Invalid', 'VALIDATION_FAILED', ['errors' => ['current_password' => ['The password is incorrect.']]]), 422)
                ->push(['message' => 'ok']),
        ]);

        $component = Livewire::test(Security::class)
            ->assertSee('Authenticator app')
            ->assertSee('8 recovery codes left')
            ->assertSee('Safari on iOS')
            ->assertSee('Signed in');

        $component->set('current_password', 'wrong')->set('password', 'New@Password1')->set('password_confirmation', 'New@Password1')
            ->call('changePassword')
            ->assertHasErrors('current_password');

        $component->set('current_password', 'Right@Pass1')->call('changePassword')
            ->assertHasNoErrors()
            ->assertSet('password', '')
            ->assertDispatched('toast');

        $component->call('revokeSession', '2')->assertDispatched('toast');

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && str_ends_with($request->url(), 'me/sessions/2'));
    }

    public function test_adding_email_method_shows_recovery_codes(): void
    {
        Http::fake([
            self::API.'me/mfa/email/confirm' => Http::response(['data' => ['method' => ['id' => 'm2', 'type' => 'email'], 'recovery_codes' => ['AAAAA-11111']]]),
            self::API.'me/mfa/email' => Http::response(['data' => ['sent_to' => 'an***@example.edu']]),
            self::API.'me/mfa' => Http::response(['data' => ['enabled' => false, 'required' => false, 'recovery_codes_remaining' => 0, 'methods' => []]]),
            self::API.'me/sessions' => Http::response(['data' => []]),
            self::API.'me/trusted-devices' => Http::response(['data' => []]),
            self::API.'me/security-events*' => Http::response(['data' => [], 'meta' => []]),
            self::API.'me' => Http::response(['data' => $this->me()]),
        ]);

        Livewire::test(Security::class)
            ->call('openSetup', 'email')
            ->assertDispatched('open-dialog')
            ->set('setupPassword', 'Secret@123')->call('startSetup')
            ->assertSet('setupStep', 'code')
            ->set('setupCode', '123456')->call('confirmSetup')
            ->assertSet('recoveryCodes', ['AAAAA-11111'])
            ->assertDispatched('close-dialog');
    }

    public function test_notification_preferences_save_only_editable_categories(): void
    {
        $matrix = [
            ['category' => 'security', 'label' => 'Security alerts', 'mandatory' => true, 'channels' => ['database' => true, 'mail' => true]],
            ['category' => 'general', 'label' => 'General announcements', 'mandatory' => false, 'channels' => ['database' => true, 'mail' => true]],
        ];

        Http::fake([self::API.'me/notification-preferences' => Http::response(['data' => $matrix])]);

        Livewire::test(NotificationPreferences::class)
            ->assertSee('Always on')
            ->set('preferences.general.mail', false)
            ->set('preferences.security.mail', false)
            ->call('save')
            ->assertDispatched('toast');

        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && ($request['preferences']['general']['mail'] ?? null) === false
            && ! isset($request['preferences']['security']));
    }

    public function test_staff_record_and_missing_record(): void
    {
        Http::fake([self::API.'me/employee-profile' => Http::sequence()
            ->push(['data' => [
                'id' => 'emp1', 'employee_code' => 'RVCEEMP0001', 'name' => 'Anita Rao', 'email' => 'a@x', 'mobile' => null, 'user_id' => 'u1',
                'staff_type' => 'teaching', 'employment_type' => 'permanent', 'status' => 'active',
                'department' => ['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science'], 'designation' => ['id' => 'g1', 'name' => 'Professor'],
                'joined_on' => '2018-06-01', 'account_status' => 'active', 'reports_to' => null, 'contract_ends_on' => null, 'left_on' => null,
                'exit_reason' => null, 'highest_qualification' => 'Ph.D.', 'specialization' => null, 'prior_experience_years' => 2.0, 'total_experience_years' => 10.4,
                'qualifications' => [['id' => 'q1', 'degree' => 'Ph.D.', 'specialization' => 'ML', 'university' => 'VTU', 'year_of_passing' => 2015, 'grade' => null, 'certificate_file_id' => null]],
            ]])
            ->push($this->apiError('No record', 'NOT_AN_EMPLOYEE'), 404)]);

        Livewire::test(StaffProfile::class)->assertSee('RVCEEMP0001')->assertSee('Computer Science')->assertSee('VTU');
        Livewire::test(StaffProfile::class)->assertSee('No staff record');
    }
}
