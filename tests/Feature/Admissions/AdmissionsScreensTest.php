<?php

namespace Tests\Feature\Admissions;

use App\Livewire\Admissions\Applications\Index as Desk;
use App\Livewire\Admissions\Applications\Show as DeskShow;
use App\Livewire\Admissions\Cycles\Index as Cycles;
use App\Livewire\Admissions\Cycles\Show as CycleShow;
use App\Livewire\Admissions\MyApplications\Index as MyApplications;
use App\Livewire\Admissions\MyApplications\Show as MyApplication;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AdmissionsScreensTest extends TestCase
{
    private const DESK = ['admissions.applications.view', 'admissions.applications.verify', 'admissions.applications.process', 'admissions.seats.allocate', 'admissions.confirm', 'admissions.cycles.manage'];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => self::DESK, 'roles' => ['admin_staff']]);
        Http::fake([self::API.'admissions/options' => Http::response(['data' => [
            'quotas' => [['value' => 'cet', 'label' => 'CET / Government', 'needs_approval' => false], ['value' => 'management', 'label' => 'Management', 'needs_approval' => true]],
            'statuses' => [['value' => 'submitted', 'label' => 'Submitted', 'final' => false], ['value' => 'offered', 'label' => 'Offered', 'final' => false]],
            'categories' => [['value' => 'GM', 'label' => 'General merit']],
        ]])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function seat(array $overrides = []): array
    {
        return array_replace(['id' => 's1', 'programme' => ['id' => 'p1', 'code' => 'BE-CSE', 'name' => 'B.E. Computer Science'], 'quota' => 'cet', 'total' => 60, 'filled' => 12, 'available' => 48, 'min_percentage' => 60.0, 'admission_fee' => 85000.0, 'needs_approval' => false], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function cycle(array $overrides = []): array
    {
        return array_replace(['id' => 'c1', 'name' => 'B.E. 2026', 'status' => 'open', 'is_accepting' => true, 'academic_year' => ['id' => 'y1', 'name' => '2026-27'], 'opens_on' => '2026-10-01', 'closes_on' => '2026-11-30', 'application_fee' => 500.0, 'offer_valid_days' => 7, 'required_documents' => ['marks_card'], 'instructions' => 'Bring originals.', 'applications_count' => 3, 'seats' => [$this->seat()]], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function application(string $status = 'submitted', array $overrides = []): array
    {
        return array_replace([
            'id' => 'a1', 'application_no' => 'RVCE/APP/2026/00001', 'status' => $status, 'status_label' => ucfirst(str_replace('_', ' ', $status)),
            'cycle' => ['id' => 'c1', 'name' => 'B.E. 2026'], 'seat' => $this->seat(),
            'applicant' => ['id' => 'u9', 'name' => 'Meera Nair', 'email' => 'meera@example.com', 'mobile' => null],
            'category' => 'GM', 'qualifying_percentage' => 82.5, 'remarks' => null, 'can_edit' => in_array($status, ['draft', 'correction_requested'], true),
            'submitted_at' => now()->toIso8601String(), 'offered_at' => null, 'offer_expires_at' => now()->addDays(5)->toIso8601String(), 'admitted_at' => null, 'register_no' => null, 'created_at' => now()->toIso8601String(),
            'personal' => ['full_name' => 'Meera Nair', 'gender' => 'female'], 'guardian' => [], 'academic' => ['percentage' => 82.5, 'board' => 'Karnataka PUC'], 'entrance' => ['exam' => 'KCET', 'rank' => 4512],
            'fee_amount' => null, 'fee_reference' => null, 'required_documents' => ['marks_card'],
            'documents' => [['id' => 'd1', 'document_type' => 'marks_card', 'file_id' => '01JBXFILE00000000000000001', 'file_name' => 'marks.pdf', 'status' => 'verified', 'remarks' => null, 'verified_at' => null]],
            'history' => [['from' => null, 'to' => 'draft', 'remarks' => null, 'by' => 'Meera Nair', 'at' => now()->toIso8601String()], ['from' => 'draft', 'to' => $status, 'remarks' => null, 'by' => 'Meera Nair', 'at' => now()->toIso8601String()]],
        ], $overrides);
    }

    public function test_admission_pages_need_permission(): void
    {
        $this->fakeShell();

        $this->signIn(['permissions' => ['identity.users.view'], 'roles' => ['faculty']])->get(route('applications.index'))->assertForbidden();
        $this->signIn(['permissions' => ['admissions.applications.view']])->get(route('admission-cycles.index'))->assertForbidden();
    }

    public function test_cycle_create_cleans_document_names(): void
    {
        Http::fake([
            self::API.'academic-years*' => Http::response(['data' => [['id' => 'y1', 'name' => '2026-27']]]),
            self::API.'admission-cycles*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $this->cycle()], 201) : Http::response(['data' => [$this->cycle()]]),
        ]);

        Livewire::test(Cycles::class)
            ->assertSee('B.E. 2026')
            ->assertSee('Accepting')
            ->call('openCreate')
            ->set('form.academic_year_id', 'y1')->set('form.name', 'MBA 2026')->set('form.opens_on', '2026-11-01')->set('form.closes_on', '2026-12-15')
            ->set('form.required_documents', 'Marks card, transfer certificate, marks card')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['required_documents'] === ['marks_card', 'transfer_certificate'] && $r['offer_valid_days'] === 7);
    }

    public function test_cycle_page_shows_seat_matrix_and_adds_seats(): void
    {
        Http::fake([
            self::API.'programmes*' => Http::response(['data' => [['id' => 'p2', 'code' => 'BE-ECE', 'name' => 'B.E. Electronics']]]),
            self::API.'admission-cycles/c1/seats' => Http::response(['data' => $this->seat(['id' => 's2'])], 201),
            self::API.'admission-cycles/c1' => Http::response(['data' => $this->cycle()]),
        ]);

        Livewire::test(CycleShow::class, ['cycle' => 'c1'])
            ->assertSee('B.E. Computer Science')
            ->assertSee('CET / Government')
            ->assertSee('12 / 60')
            ->call('editSeat')
            ->set('seatForm.programme_id', 'p2')->set('seatForm.quota', 'management')->set('seatForm.total', '30')
            ->call('saveSeat')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'admission-cycles/c1/seats') && $r['quota'] === 'management' && $r['total'] === 30 && $r['min_percentage'] === null);
    }

    public function test_desk_list_filters_by_cycle_and_shows_counts(): void
    {
        Http::fake([
            self::API.'applications/summary*' => Http::response(['data' => ['submitted' => 4, 'offered' => 1]]),
            self::API.'applications*' => Http::response(['data' => [$this->application()], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]]),
            self::API.'admission-cycles*' => Http::response(['data' => [$this->cycle()]]),
            self::API.'programmes*' => Http::response(['data' => []]),
        ]);

        Livewire::test(Desk::class)
            ->assertSee('Meera Nair')
            ->assertSee('RVCE/APP/2026/00001')
            ->set('filters.cycle', 'c1')
            ->assertSeeInOrder(['Submitted', '4']);

        Http::assertSent(fn (Request $r) => str_contains(urldecode($r->url()), 'applications/summary') && str_contains(urldecode($r->url()), 'filter[cycle]=c1'));
    }

    public function test_desk_verifies_offers_and_confirms(): void
    {
        // A tiny stand-in for the API: each action moves the state; reads return the current state.
        // (The applicant accepts the offer elsewhere, so "offer" lands on offer_accepted here.)
        $state = 'submitted';
        $after = ['complete-verification' => 'eligible', 'offer' => 'offer_accepted', 'confirm' => 'admitted'];
        $admitted = ['register_no' => 'RVCE26BECSE001', 'fee_amount' => 85000.0, 'fee_reference' => 'RCPT-9', 'admitted_at' => now()->toIso8601String()];

        Http::fake([self::API.'applications/a1*' => function (Request $r) use (&$state, $after, $admitted) {
            if ($r->method() === 'POST') {
                $state = $after[basename(parse_url($r->url(), PHP_URL_PATH))] ?? $state;
            }

            return Http::response(['data' => $this->application($state, $state === 'admitted' ? $admitted : [])]);
        }]);

        Livewire::test(DeskShow::class, ['application' => 'a1'])
            ->assertSee('Complete verification')
            ->assertSee('KCET')
            ->call('completeVerification')
            ->assertSee('Offer seat')
            ->call('offer')
            ->call('openConfirm')
            ->assertSet('fee.amount', '85000')
            ->call('confirm')
            ->assertHasErrors(['fee.reference'])
            ->set('fee.reference', 'RCPT-9')
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertSee('RVCE26BECSE001');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'a1/confirm') && $r['fee_amount'] === 85000.0 && $r['fee_reference'] === 'RCPT-9');
    }

    public function test_corrections_and_rejections_need_remarks(): void
    {
        Http::fake([
            self::API.'applications/a1/request-correction' => Http::response(['data' => $this->application('correction_requested')]),
            self::API.'applications/a1' => Http::response(['data' => $this->application('submitted')]),
        ]);

        Livewire::test(DeskShow::class, ['application' => 'a1'])
            ->call('openDecision', 'request-correction')
            ->call('decide', 'request-correction')
            ->assertHasErrors(['remarks'])
            ->set('remarks', 'Upload a clear marks card')
            ->call('decide', 'request-correction')
            ->assertHasNoErrors()
            ->call('decide', 'delete-everything');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'request-correction') && $r['remarks'] === 'Upload a clear marks card');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'delete-everything'));
    }

    public function test_applicant_applies_from_an_open_cycle(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['applicant']]);
        Http::fake([
            self::API.'admissions/cycles/open' => Http::response(['data' => [$this->cycle()]]),
            self::API.'me/applications' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $this->application('draft')], 201) : Http::response(['data' => []]),
        ]);

        Livewire::test(MyApplications::class)
            ->assertSee('B.E. 2026')
            ->assertSee('48 seats left')
            ->call('apply', 's1')
            ->assertRedirect(route('my-applications.show', 'a1'));
    }

    public function test_applicant_fills_uploads_and_submits(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['applicant']]);
        Http::fake([
            self::API.'files' => Http::response(['data' => ['id' => '01JBXFILE00000000000000002']], 201),
            self::API.'me/applications/a1/documents' => Http::response(['data' => $this->application('draft')]),
            self::API.'me/applications/a1/submit' => Http::response(['data' => $this->application('submitted')]),
            self::API.'me/applications/a1' => fn (Request $r) => Http::response(['data' => $this->application('draft', ['documents' => [], 'qualifying_percentage' => null, 'academic' => []])]),
        ]);

        Livewire::test(MyApplication::class, ['application' => 'a1'])
            ->assertSee('Qualifying exam %')
            ->assertSee('Marks card')
            ->set('uploads.marks_card', UploadedFile::fake()->create('marks.pdf', 100, 'application/pdf'))
            ->assertHasNoErrors()
            ->set('form.academic.percentage', '82.5')->set('form.academic.year_of_passing', '2026')
            ->call('submit')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'a1/documents') && $r['document_type'] === 'marks_card' && $r['file_id'] === '01JBXFILE00000000000000002');
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['academic'] === ['percentage' => 82.5, 'board' => 'Karnataka PUC', 'year_of_passing' => 2026] || ($r->method() === 'PUT' && ($r['academic']['percentage'] ?? null) === 82.5));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'a1/submit'));
    }

    public function test_applicant_accepts_offer(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['applicant']]);
        Http::fake([
            self::API.'me/applications/a1/accept-offer' => Http::response(['data' => $this->application('offer_accepted')]),
            self::API.'me/applications/a1' => Http::response(['data' => $this->application('offered')]),
        ]);

        Livewire::test(MyApplication::class, ['application' => 'a1'])
            ->assertSee('You have been offered a seat')
            ->assertSee('85,000')
            ->call('acceptOffer');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'a1/accept-offer'));
    }
}
