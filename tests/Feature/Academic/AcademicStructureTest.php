<?php

namespace Tests\Feature\Academic;

use App\Livewire\Academic\AcademicYears\Index as AcademicYears;
use App\Livewire\Academic\Batches\Index as Batches;
use App\Livewire\Academic\Departments\Index as Departments;
use App\Livewire\Academic\Programmes\Index as Programmes;
use App\Livewire\Academic\Rooms\Index as Rooms;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    private const MANAGE = ['institution.departments.manage', 'institution.programmes.manage', 'institution.batches.manage', 'institution.rooms.manage', 'institution.calendar.manage', 'hr.employees.view'];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => self::MANAGE]);
    }

    /**
     * @return array<string, mixed>
     */
    private function department(): array
    {
        return ['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science', 'type' => 'academic', 'status' => 'active', 'description' => null, 'email' => null, 'phone' => null, 'established_year' => 1980, 'programmes_count' => 2, 'active_staff_count' => 31];
    }

    private function fakeLookups(): void
    {
        Http::fake([
            self::API.'employees*' => Http::response(['data' => [['id' => 'e1', 'user_id' => 'u7', 'name' => 'Dr. Kavya', 'employee_code' => 'EMP-0007', 'designation' => ['id' => 'g1', 'name' => 'Professor']]], 'meta' => ['total' => 1]]),
            self::API.'programmes*' => Http::response(['data' => [['id' => 'p1', 'code' => 'BE-CSE', 'name' => 'B.E. Computer Science', 'level' => 'ug', 'duration_years' => 4, 'total_terms' => 8, 'intake_capacity' => 180, 'regulation' => '2022 Scheme', 'status' => 'active', 'department' => ['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science']]]]),
        ]);
    }

    public function test_lists_open_to_members_without_manage_buttons(): void
    {
        Http::fake([self::API.'departments*' => Http::response(['data' => [$this->department()]])]);
        $this->signIn(['permissions' => []]);

        Livewire::test(Departments::class)
            ->assertSee('Computer Science')
            ->assertSee('31')
            ->assertDontSeeHtml('wire:click="openCreate"')
            ->assertDontSeeHtml('wire:click="openEdit(');
    }

    public function test_department_create_sends_nulls_and_numbers(): void
    {
        Http::fake([self::API.'departments*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $this->department()], 201) : Http::response(['data' => []])]);

        Livewire::test(Departments::class)
            ->call('openCreate')
            ->set('form.code', 'ECE')->set('form.name', 'Electronics')->set('form.established_year', '1985')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog', 'record-form');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['code'] === 'ECE' && $r['established_year'] === 1985 && $r['email'] === null);
    }

    public function test_head_of_department_is_picked_from_teaching_staff(): void
    {
        Http::fake([
            self::API.'departments/d1/hod' => Http::response(['data' => []]),
            self::API.'departments/d1' => Http::response(['data' => $this->department() + ['hod' => null]]),
            self::API.'departments*' => Http::response(['data' => [$this->department()]]),
        ]);
        $this->fakeLookups();

        Livewire::test(Departments::class)
            ->call('openHod', 'd1')
            ->assertSee('Dr. Kavya')
            ->set('hodUserId', 'u7')
            ->call('assignHod')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_contains(urldecode($r->url()), 'employees') && str_contains(urldecode($r->url()), 'filter[staff_type]=teaching') && str_contains(urldecode($r->url()), 'filter[department]=d1'));
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'departments/d1/hod') && $r['user_id'] === 'u7');
    }

    public function test_business_rule_errors_are_shown_on_delete(): void
    {
        Http::fake([
            self::API.'departments/d1' => Http::response($this->apiError('This department still has programmes.', 'DEPARTMENT_IN_USE'), 409),
            self::API.'departments*' => Http::response(['data' => [$this->department()]]),
        ]);

        Livewire::test(Departments::class)
            ->call('askDelete', 'd1')
            ->call('delete')
            ->assertSet('formError', 'This department still has programmes.')
            ->assertSet('deleteId', 'd1');
    }

    public function test_programme_payload_casts_numbers_and_skips_empty_terms(): void
    {
        Http::fake([self::API.'departments*' => Http::response(['data' => [$this->department()]])]);
        $this->fakeLookups();
        Http::fake([self::API.'programmes' => Http::response(['data' => []], 201)]);

        Livewire::test(Programmes::class)
            ->assertSee('B.E. Computer Science')
            ->call('openCreate')
            ->set('form.department_id', 'd1')->set('form.code', 'ME-CSE')->set('form.name', 'M.E. CSE')->set('form.level', 'pg')->set('form.duration_years', '2')->set('form.intake_capacity', '18')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['duration_years'] === 2 && $r['intake_capacity'] === 18 && ! array_key_exists('total_terms', $r->data()));
    }

    public function test_batch_create_splits_sections_and_section_saves(): void
    {
        $batch = ['id' => 'b1', 'name' => 'BE-CSE 2026', 'admission_year' => 2026, 'graduation_year' => 2030, 'status' => 'active', 'programme' => ['id' => 'p1', 'code' => 'BE-CSE', 'name' => 'B.E. Computer Science'], 'sections' => [['id' => 's1', 'name' => 'A', 'capacity' => 60, 'class_advisor' => null]]];
        Http::fake([
            self::API.'departments*' => Http::response(['data' => [$this->department()]]),
            self::API.'batches/b1/sections' => Http::response(['data' => []], 201),
            self::API.'batches/b1' => Http::response(['data' => $batch]),
            self::API.'batches*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $batch], 201) : Http::response(['data' => [$batch]]),
        ]);
        $this->fakeLookups();

        Livewire::test(Batches::class)
            ->assertSee('BE-CSE 2026')
            ->call('openCreate')
            ->set('create.programme_id', 'p1')->set('create.admission_year', '2026')->set('create.sections', 'a, b ,B, c')
            ->call('store')
            ->assertHasNoErrors()
            ->call('openBatch', 'b1')
            ->call('editSection')
            ->set('sectionForm.name', 'd')->set('sectionForm.capacity', '60')->set('sectionForm.class_advisor_id', 'u7')
            ->call('saveSection')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/batches') && $r['sections'] === ['A', 'B', 'C'] && $r['admission_year'] === 2026);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'batches/b1/sections') && $r['name'] === 'D' && $r['capacity'] === 60 && $r['class_advisor_id'] === 'u7');
    }

    public function test_academic_year_actions_and_calendar_events(): void
    {
        $year = ['id' => 'y1', 'name' => '2026-27', 'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31', 'is_current' => false, 'status' => 'planned', 'terms' => [['id' => 't1', 'name' => 'Odd Semester 2026', 'type' => 'odd', 'starts_on' => '2026-08-01', 'ends_on' => '2026-12-15', 'is_current' => false, 'status' => 'planned']]];
        Http::fake([
            self::API.'academic-years/y1/make-current' => Http::response(['data' => $year]),
            self::API.'calendar-events*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'ev1']], 201) : Http::response(['data' => [['id' => 'ev0', 'title' => 'Kannada Rajyotsava', 'description' => null, 'type' => 'holiday', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-01', 'is_holiday' => true, 'audience' => 'all', 'academic_year' => null]]]),
            self::API.'academic-years*' => Http::response(['data' => [$year]]),
        ]);

        $component = Livewire::test(AcademicYears::class)
            ->assertSee('2026-27')
            ->assertSee('Odd Semester 2026')
            ->call('confirm', 'year', 'y1', 'make-current')
            ->assertSet('pending', ['type' => 'year', 'id' => 'y1', 'action' => 'make-current'])
            ->call('runPending')
            ->assertSet('pending', null);

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'academic-years/y1/make-current'));

        $component->set('tab', 'events')->set('month', '2026-11')
            ->assertSee('Kannada Rajyotsava')
            ->call('editEvent')
            ->set('eventForm.title', 'Hackathon')->set('eventForm.type', 'event')->set('eventForm.starts_on', '2026-11-20')->set('eventForm.is_holiday', false)
            ->call('saveEvent')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_contains(urldecode($r->url()), 'filter[from]=2026-11-01') && str_contains(urldecode($r->url()), 'filter[to]=2026-11-30'));
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'calendar-events') && $r['title'] === 'Hackathon' && $r['ends_on'] === null && $r['is_holiday'] === false);
    }

    public function test_confirm_ignores_unknown_actions(): void
    {
        Http::fake([self::API.'academic-years*' => Http::response(['data' => []])]);

        Livewire::test(AcademicYears::class)
            ->call('confirm', 'year', 'y1', 'drop-database')
            ->assertSet('pending.action', 'close');
    }

    public function test_rooms_list_pages_and_saves(): void
    {
        $room = ['id' => 'rm1', 'code' => 'B-204', 'name' => 'Seminar hall', 'building' => 'B Block', 'floor' => '2', 'type' => 'seminar_hall', 'capacity' => 120, 'status' => 'active', 'department' => null];
        Http::fake([
            self::API.'departments*' => Http::response(['data' => [$this->department()]]),
            self::API.'rooms/rm1' => fn (Request $r) => Http::response(['data' => $room]),
            self::API.'rooms*' => Http::response(['data' => [$room], 'meta' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 25, 'total' => 60]]),
        ]);

        Livewire::test(Rooms::class)
            ->assertSee('B-204')
            ->assertSee('B Block · Floor 2')
            ->assertSee('Shared')
            ->call('gotoPage', 2)
            ->call('openEdit', 'rm1')
            ->assertSet('form.capacity', '120')
            ->set('form.capacity', '150')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'page=2'));
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['capacity'] === 150 && $r['department_id'] === null);
    }
}
