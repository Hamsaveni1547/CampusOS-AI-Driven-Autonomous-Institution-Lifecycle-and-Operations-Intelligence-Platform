<?php

namespace Tests\Feature\Auth;

use App\Api\CampusApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GatesAndErrorsTest extends TestCase
{
    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/')->assertRedirect(url('/login'));
    }

    public function test_signed_in_shell_shows_only_permitted_navigation(): void
    {
        Route::middleware('web')->get('/staff-test', fn () => 'ok')->name('staff.index');
        Route::middleware('web')->get('/users-test', fn () => 'ok')->name('users.index');
        Route::getRoutes()->refreshNameLookups();

        $this->signIn(['permissions' => ['hr.employees.view']])
            ->get('/')
            ->assertOk()
            ->assertSee('Welcome, Anita Rao')
            ->assertSee('RV College of Engineering')
            ->assertSee(url('/staff-test'))
            ->assertDontSee(url('/users-test'))
            ->assertHeader('Content-Security-Policy');
    }

    public function test_stale_profile_is_refreshed(): void
    {
        Http::fake([self::API.'me' => Http::response(['data' => $this->me(['name' => 'Anita R. Rao'])])]);

        $this->signIn()
            ->withSession(['campus.me_refreshed_at' => now()->subHour()->getTimestamp()])
            ->get('/')
            ->assertOk()
            ->assertSee('Welcome, Anita R. Rao');
    }

    public function test_pending_requirement_redirects_to_its_screen(): void
    {
        Route::middleware(['web', 'campus.auth'])->get('/required/password', fn () => 'change password')->name('required.password');
        Route::getRoutes()->refreshNameLookups();

        $this->signIn(['pending_requirement' => 'PASSWORD_CHANGE_REQUIRED'])
            ->get('/')
            ->assertRedirect('/required/password');
    }

    public function test_permission_middleware_blocks_pages(): void
    {
        Route::middleware(['web', 'campus.auth', 'permission:identity.users.view'])->get('/_test/users', fn () => 'users');
        Route::middleware(['web', 'campus.auth', 'permission:platform'])->get('/_test/platform', fn () => 'platform');

        $this->signIn()->get('/_test/users')->assertForbidden()->assertSee("You don't have access");
        $this->signIn(['permissions' => ['identity.users.view']])->get('/_test/users')->assertOk();
        $this->signIn()->get('/_test/platform')->assertForbidden();
        $this->signIn(['is_platform_admin' => true])->get('/_test/platform')->assertOk();
    }

    public function test_api_errors_render_friendly_pages(): void
    {
        Route::middleware(['web', 'campus.auth'])->get('/_test/fetch', fn (CampusApi $api) => $api->get('rooms')->json());

        Http::fake([self::API.'rooms' => Http::sequence()
            ->push($this->apiError('Nope', 'FORBIDDEN'), 403)
            ->push($this->apiError('Missing', 'NOT_FOUND'), 404)
            ->push($this->apiError('Boom', 'SERVER_ERROR'), 500)
            ->push($this->apiError('Session ended', 'UNAUTHENTICATED'), 401)]);

        $this->signIn()->get('/_test/fetch')->assertStatus(403)->assertSee("You don't have access");

        $this->signIn()->get('/_test/fetch')->assertStatus(404)->assertSee('Page not found');

        $this->signIn()->get('/_test/fetch')->assertStatus(500)->assertSee('trace-from-api-123');

        $this->signIn()->get('/_test/fetch')->assertRedirect(url('/login'))->assertSessionMissing('campus.token');
    }

    public function test_unreachable_api_shows_service_unavailable(): void
    {
        Route::middleware(['web', 'campus.auth'])->get('/_test/down', fn (CampusApi $api) => $api->get('rooms')->json());
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->signIn()->get('/_test/down')->assertStatus(503)->assertSee('temporarily unavailable');
    }

    public function test_logout_revokes_token_and_clears_session(): void
    {
        Http::fake([self::API.'auth/logout' => Http::response(['message' => 'Signed out.'])]);

        $this->signIn()->post('/logout')->assertRedirect('/')->assertSessionMissing('campus.token');

        Http::assertSent(fn ($request) => $request->url() === self::API.'auth/logout');
    }

    public function test_switching_institution_requires_membership(): void
    {
        Http::fake([self::API.'me' => Http::response(['data' => $this->me()])]);

        $this->signIn()->post('/institution/switch', ['institution' => 'SOMEONE-ELSES'])->assertForbidden();

        $this->signIn()->post('/institution/switch', ['institution' => '01JBXINST00000000000000001'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('campus.institution', '01JBXINST00000000000000001');
    }
}
