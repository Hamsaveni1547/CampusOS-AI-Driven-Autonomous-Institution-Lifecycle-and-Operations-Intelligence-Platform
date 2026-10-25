<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Designations\Index as Designations;
use App\Livewire\Hr\Staff\Index as Staff;
use App\Livewire\Hr\Staff\Show as StaffShow;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class StaffTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['hr.employees.view', 'hr.employees.manage', 'hr.designations.manage', 'identity.users.view']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function employee(): array
    {
        return [
            'id' => 'e1', 'employee_code' => 'EMP-0007', 'name' => 'Dr. Kavya Rao', 'email' => 'kavya@example.edu', 'mobile' => null, 'user_id' => 'u7',
            'staff_type' => 'teaching', 'employment_type' => 'permanent', 'status' => 'active', 'department' => ['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science'],
            'designation' => ['id' => 'g1', 'name' => 'Professor'], 'joined_on' => '2015-07-01', 'account_status' => 'active', 'reports_to' => null, 'contract_ends_on' => null,
            'left_on' => null, 'exit_reason' => null, 'highest_qualification' => 'Ph.D.', 'specialization' => 'Machine learning', 'prior_experience_years' => 3.0, 'total_experience_years' => 14.3,
            'qualifications' => [['id' => 'q1', 'degree' => 'Ph.D.', 'specialization' => 'ML', 'university' => 'IISc', 'year_of_passing' => 2014, 'grade' => null, 'certificate_file_id' => '01JBXFILE00000000000000009']],
            'personal' => ['gender' => 'female', 'date_of_birth' => '1985-04-12', 'address' => null, 'emergency_contact_name' => null, 'emergency_contact_phone' => null],
        ];
    }

    private function fakeLookups(): void
    {
        Http::fake([
            self::API.'departments*' => Http::response(['data' => [['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science']]]),
            self::API.'designations*' => Http::response(['data' => [['id' => 'g1', 'name' => 'Professor', 'category' => 'teaching', 'rank' => 10, 'status' => 'active', 'employees_count' => 4]]]),
        ]);
    }

    public function test_staff_page_needs_permission(): void
    {
        $this->fakeShell();

        // me() merges recursively, so name a different permission to drop the default hr.employees.view.
        $this->signIn(['permissions' => ['identity.users.view']])->get(route('staff.index'))->assertForbidden();
    }

    public function test_staff_list_and_create_redirects_to_new_record(): void
    {
        Http::fake([
            self::API.'employees' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $this->employee()], 201) : Http::response(['data' => [], 'meta' => ['total' => 0]]),
            self::API.'employees*' => Http::response(['data' => [$this->employee()], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]]),
        ]);
        $this->fakeLookups();

        Livewire::test(Staff::class)
            ->assertSee('Dr. Kavya Rao')
            ->assertSee('EMP-0007')
            ->call('openCreate')
            ->set('form.name', 'Ravi Kumar')->set('form.email', 'ravi@example.edu')->set('form.designation_id', 'g1')->set('form.send_invite', false)
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('staff.show', 'e1'));

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['send_invite'] === false && $r['designation_id'] === 'g1' && ! array_key_exists('employee_code', $r->data()));
    }

    public function test_staff_page_status_change_and_qualification_upload(): void
    {
        Http::fake([
            self::API.'employees/e1/status' => Http::response(['data' => $this->employee()]),
            self::API.'employees/e1/qualifications' => Http::response(['data' => []], 201),
            self::API.'files' => Http::response(['data' => ['id' => '01JBXFILE00000000000000010']], 201),
            self::API.'employees/e1' => Http::response(['data' => $this->employee()]),
        ]);

        Livewire::test(StaffShow::class, ['employee' => 'e1'])
            ->assertSee('Dr. Kavya Rao')
            ->assertSee('IISc')
            ->assertSee('14 years')
            ->assertSee(route('files.download', '01JBXFILE00000000000000009'))
            ->call('openStatus')
            ->set('statusForm.status', 'resigned')->set('statusForm.reason', 'Moved abroad')
            ->call('saveStatus')
            ->assertHasNoErrors()
            ->call('editQualification')
            ->set('qualificationForm.degree', 'M.Tech')->set('qualificationForm.year_of_passing', '2009')
            ->set('certificate', UploadedFile::fake()->create('mtech.pdf', 120, 'application/pdf'))
            ->call('saveQualification')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'employees/e1/status') && $r['status'] === 'resigned' && $r['reason'] === 'Moved abroad');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/files') && $r->isMultipart());
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'employees/e1/qualifications') && $r['certificate_file_id'] === '01JBXFILE00000000000000010' && $r['year_of_passing'] === 2009);
    }

    public function test_staff_page_hides_changes_from_viewers(): void
    {
        Http::fake([self::API.'employees/e1' => Http::response(['data' => $this->employee()])]);
        $this->signIn(['permissions' => ['hr.employees.view']]);

        Livewire::test(StaffShow::class, ['employee' => 'e1'])
            ->assertDontSee('Change status')
            ->assertDontSeeHtml('wire:click="edit"');
    }

    public function test_file_download_streams_from_the_api(): void
    {
        $this->fakeShell([self::API.'files/01JBXFILE00000000000000009/download' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="phd.pdf"'])]);

        $this->get(route('files.download', '01JBXFILE00000000000000009'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_designations_edit_from_list(): void
    {
        $this->fakeLookups();
        Http::fake([self::API.'designations/g1' => Http::response(['data' => []])]);

        Livewire::test(Designations::class)
            ->assertSee('Professor')
            ->call('openEdit', 'g1')
            ->assertSet('form.name', 'Professor')
            ->set('form.rank', '5')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && str_ends_with($r->url(), 'designations/g1') && $r['rank'] === 5);
    }
}
