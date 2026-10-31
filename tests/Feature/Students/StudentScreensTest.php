<?php

namespace Tests\Feature\Students;

use App\Livewire\Students\ChangeRequests;
use App\Livewire\Students\Index;
use App\Livewire\Students\MyRecord;
use App\Livewire\Students\Show;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class StudentScreensTest extends TestCase
{
    private const OFFICE = ['students.records.view', 'students.records.create', 'students.records.update', 'students.records.import', 'students.status.change', 'students.documents.verify', 'students.changes.approve'];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => self::OFFICE, 'roles' => ['admin_staff']]);
        Http::fake([
            self::API.'students/st1/academic-history' => Http::response(['data' => ['cgpa' => 7.5, 'backlogs' => 1, 'results' => [[
                'cycle' => ['id' => 'cy1', 'name' => 'Nov 2026', 'published_at' => null], 'sgpa' => 7.5, 'cgpa' => 7.5, 'credits_earned' => 3.0, 'credits_registered' => 7.0,
                'courses' => [['id' => 'rg1', 'code' => '21CS51', 'title' => 'DBMS', 'credits' => 4.0, 'internal' => 10.0, 'external' => null, 'grade' => 'AB', 'passed' => false, 'revaluation' => null, 'can_request_revaluation' => false]],
            ]]]]),
            self::API.'students/options' => Http::response(['data' => [
                'statuses' => [['value' => 'active', 'label' => 'Active', 'final' => false], ['value' => 'on_leave', 'label' => 'On leave', 'final' => false], ['value' => 'graduated', 'label' => 'Graduated', 'final' => true]],
                'guardian_relations' => [['value' => 'father', 'label' => 'Father'], ['value' => 'mother', 'label' => 'Mother']],
                'id_proof_types' => [['value' => 'aadhaar', 'label' => 'Aadhaar']],
                'blood_groups' => [['value' => 'O+', 'label' => 'O+']],
                'genders' => [['value' => 'female', 'label' => 'Female'], ['value' => 'male', 'label' => 'Male']],
                'self_editable' => ['address'], 'requestable' => ['name'],
            ]]),
            self::API.'admissions/options' => Http::response(['data' => ['quotas' => [['value' => 'cet', 'label' => 'CET / Government', 'needs_approval' => false]], 'statuses' => [], 'categories' => [['value' => 'GM', 'label' => 'General merit'], ['value' => 'SC', 'label' => 'Scheduled Caste']]]]),
            self::API.'programmes*' => Http::response(['data' => [['id' => 'p1', 'code' => 'BE-CSE', 'name' => 'B.E. Computer Science']]]),
            self::API.'departments*' => Http::response(['data' => [['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science']]]),
            self::API.'batches*' => Http::response(['data' => [['id' => 'b1', 'name' => 'BE-CSE 2026', 'sections' => [['id' => 's1', 'name' => 'A']]]]]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function student(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 'st1', 'user_id' => 'u1', 'register_no' => 'RVCE26BECSE001', 'roll_no' => '01', 'name' => 'Ravi Kumar', 'email' => 'ravi@example.edu', 'mobile' => null,
            'programme' => ['id' => 'p1', 'code' => 'BE-CSE', 'name' => 'B.E. Computer Science'], 'department' => ['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science'],
            'batch' => ['id' => 'b1', 'name' => 'BE-CSE 2026'], 'section' => ['id' => 's1', 'name' => 'A'], 'current_term_number' => 1,
            'admission_type' => 'cet', 'category' => 'GM', 'status' => 'active', 'status_label' => 'Active', 'admitted_on' => '2026-08-01', 'photo_file_id' => null,
            'personal' => ['date_of_birth' => '2008-03-10', 'gender' => 'male', 'blood_group' => null, 'nationality' => 'Indian', 'religion' => null, 'mother_tongue' => 'Kannada', 'address' => 'Bengaluru', 'permanent_address' => null, 'id_proof_type' => 'aadhaar', 'id_proof_number' => '••••••••1234'],
            'application_no' => 'RVCE/APP/2026/00001', 'left_on' => null, 'exit_reason' => null,
            'guardians' => [['id' => 'g1', 'relation' => 'father', 'name' => 'Suresh', 'phone' => '9876543210', 'email' => null, 'occupation' => 'Farmer', 'annual_income' => null, 'is_primary' => true]],
            'documents' => [['id' => 'doc1', 'document_type' => 'transfer_certificate', 'file_id' => '01JBXFILE00000000000000001', 'file_name' => 'tc.pdf', 'status' => 'pending', 'remarks' => null, 'reviewed_at' => null]],
            'status_history' => [['from' => null, 'to' => 'active', 'effective_on' => '2026-08-01', 'reason' => 'Enrolled', 'by' => 'Office']],
            'change_requests' => [],
        ], $overrides);
    }

    public function test_students_page_needs_permission(): void
    {
        $this->fakeShell();

        $this->signIn(['permissions' => ['identity.users.view'], 'roles' => ['student']])->get(route('students.index'))->assertForbidden();
    }

    public function test_list_filters_and_add_student(): void
    {
        Http::fake([self::API.'students*' => fn (Request $r) => $r->method() === 'POST'
            ? Http::response(['data' => $this->student(['id' => 'st2'])], 201)
            : Http::response(['data' => [$this->student()], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]])]);

        Livewire::test(Index::class)
            ->assertSee('Ravi Kumar')
            ->assertSee('RVCE26BECSE001')
            ->assertSeeInOrder(['BE-CSE 2026', 'Roll 01'])
            ->set('filters.batch', 'b1')
            ->set('filters.status', 'all')
            ->call('openCreate')
            ->set('form.name', 'Asha')->set('form.email', 'asha@example.edu')->set('form.programme_id', 'p1')->set('form.admission_type', 'cet')->set('form.send_invite', false)
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('students.show', 'st2'));

        Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains(urldecode($r->url()), 'filter[status]=all') && str_contains(urldecode($r->url()), 'filter[batch]=b1'));
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['email'] === 'asha@example.edu' && $r['send_invite'] === false && ! array_key_exists('register_no', $r->data()));
    }

    public function test_student_page_placement_status_guardians_and_reviews(): void
    {
        Http::fake([
            self::API.'students/st1/placement' => Http::response(['data' => $this->student()]),
            self::API.'students/st1/status' => Http::response(['data' => $this->student()]),
            self::API.'students/st1/guardians' => Http::response(['data' => $this->student()]),
            self::API.'students/st1/documents/doc1/review' => Http::response(['data' => $this->student()]),
            self::API.'student-change-requests/cr1/review' => Http::response(['data' => []]),
            self::API.'students/st1' => Http::response(['data' => $this->student(['change_requests' => [['id' => 'cr1', 'changes' => ['date_of_birth' => '2008-04-10'], 'reason' => 'Typo', 'status' => 'pending', 'remarks' => null, 'reviewed_by' => null, 'reviewed_at' => null, 'created_at' => now()->toIso8601String()]]])]),
        ]);

        Livewire::test(Show::class, ['student' => 'st1'])
            ->assertSee('Suresh')
            ->assertSeeInOrder(['CGPA', '7.5', 'backlogs 1', 'Nov 2026', '21CS51', 'AB'])
            ->assertSee('••••••••1234')
            ->assertSee('Correction requested by the student')
            ->call('openPlacement')
            ->assertSet('placement.section_id', 's1')
            ->set('placement.roll_no', '07')->set('placement.current_term_number', '2')
            ->call('savePlacement')
            ->assertHasNoErrors()
            ->call('openStatus')
            ->set('statusForm.status', 'on_leave')->set('statusForm.reason', 'Medical')
            ->call('saveStatus')
            ->call('openGuardians')
            ->call('addGuardian')
            ->set('guardians.1.relation', 'mother')->set('guardians.1.name', 'Lata')
            ->call('saveGuardians')
            ->assertHasNoErrors()
            ->call('approveDocument', 'doc1')
            ->call('askReject', 'change', 'cr1')
            ->call('reject')
            ->assertHasErrors(['remarks'])
            ->set('remarks', 'Attach birth certificate')
            ->call('reject')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'st1/placement') && $r['roll_no'] === '07' && $r['current_term_number'] === 2 && $r['section_id'] === 's1');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'st1/status') && $r['status'] === 'on_leave');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'st1/guardians') && count($r['guardians']) === 2 && $r['guardians'][1]['name'] === 'Lata');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'doc1/review') && $r['approved'] === true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'cr1/review') && $r['approved'] === false && $r['remarks'] === 'Attach birth certificate');
    }

    public function test_edit_keeps_masked_id_number(): void
    {
        Http::fake([self::API.'students/st1' => Http::response(['data' => $this->student()])]);

        Livewire::test(Show::class, ['student' => 'st1'])
            ->call('edit')
            ->set('form.blood_group', 'O+')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['blood_group'] === 'O+' && ! array_key_exists('id_proof_number', $r->data()));
    }

    public function test_correction_queue_applies_requests(): void
    {
        Http::fake([
            self::API.'student-change-requests/cr1/review' => Http::response(['data' => []]),
            self::API.'student-change-requests*' => Http::response(['data' => [['id' => 'cr1', 'student' => ['id' => 'st1', 'name' => 'Ravi Kumar', 'register_no' => 'RVCE26BECSE001'], 'changes' => ['category' => 'SC'], 'reason' => 'Wrong category', 'status' => 'pending', 'remarks' => null, 'reviewed_by' => null, 'reviewed_at' => null, 'created_at' => now()->toIso8601String()]], 'meta' => ['total' => 1]]),
        ]);

        Livewire::test(ChangeRequests::class)
            ->assertSee('Ravi Kumar')
            ->assertSee('Wrong category')
            ->call('approve', 'cr1');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'cr1/review') && $r['approved'] === true);
    }

    public function test_student_edits_contact_requests_correction_and_uploads(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['student']]);
        Http::fake([
            self::API.'files' => Http::response(['data' => ['id' => '01JBXFILE00000000000000005']], 201),
            self::API.'me/student/documents' => Http::response(['data' => $this->student()]),
            self::API.'me/student/change-requests' => Http::response(['data' => $this->student()]),
            self::API.'me/student' => Http::response(['data' => $this->student()]),
        ]);

        Livewire::test(MyRecord::class)
            ->assertSee('RVCE26BECSE001')
            ->assertSee('Suresh')
            ->call('editContact')
            ->assertSet('contact.address', 'Bengaluru')
            ->set('contact.address', 'Mysuru')
            ->call('saveContact')
            ->call('openCorrection')
            ->set('reason', 'Typo')
            ->call('requestCorrection')
            ->assertHasErrors(['correction'])
            ->set('correction.date_of_birth', '2008-04-10')
            ->call('requestCorrection')
            ->assertHasNoErrors()
            ->set('documentType', 'Birth certificate')
            ->set('documentFile', UploadedFile::fake()->create('birth.pdf', 40, 'application/pdf'))
            ->call('uploadDocument')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['address'] === 'Mysuru');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'change-requests') && $r['changes'] === ['date_of_birth' => '2008-04-10']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'me/student/documents') && $r['document_type'] === 'birth_certificate');
    }
}
