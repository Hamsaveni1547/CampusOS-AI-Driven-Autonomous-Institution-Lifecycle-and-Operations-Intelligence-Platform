<?php

namespace Tests\Feature\Academics;

use App\Livewire\Academics\ClassShow;
use App\Livewire\Academics\Courses\Index as Courses;
use App\Livewire\Academics\Courses\Show as CourseShow;
use App\Livewire\Academics\Curricula\Show as CurriculumShow;
use App\Livewire\Academics\MyClasses;
use App\Livewire\Academics\MyRegistration;
use App\Livewire\Academics\Offerings\Index as Offerings;
use App\Livewire\Academics\Registrations;
use App\Livewire\Academics\Workload;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicsScreensTest extends TestCase
{
    private const HOD = ['academics.catalogue.view', 'academics.courses.manage', 'academics.curriculum.manage', 'academics.offerings.manage', 'academics.allocations.manage', 'academics.syllabus.track', 'academics.materials.manage'];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => self::HOD, 'roles' => ['hod']]);
        Http::fake([
            self::API.'academics/options' => Http::response(['data' => [
                'course_types' => [['value' => 'theory', 'label' => 'Theory'], ['value' => 'lab', 'label' => 'Lab']],
                'components' => [['value' => 'theory', 'label' => 'Theory'], ['value' => 'lab', 'label' => 'Lab']],
                'curriculum_statuses' => [['value' => 'draft', 'label' => 'Draft'], ['value' => 'submitted', 'label' => 'Submitted'], ['value' => 'approved', 'label' => 'Approved']],
                'categories' => [['value' => 'core', 'label' => 'Professional core'], ['value' => 'lab', 'label' => 'Laboratory']],
            ]]),
            self::API.'departments*' => Http::response(['data' => [['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science']]]),
            self::API.'terms*' => Http::response(['data' => [['id' => 't1', 'name' => 'Odd 2026', 'is_current' => true, 'starts_on' => '2026-08-01']]]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function course(array $overrides = []): array
    {
        return array_replace(['id' => 'c1', 'code' => '21CS51', 'title' => 'Database Management Systems', 'type' => 'theory', 'type_label' => 'Theory', 'credits' => 4.0, 'lecture_hours' => 3, 'tutorial_hours' => 1, 'practical_hours' => 0, 'ltp' => '3-1-0', 'regulation' => '2021', 'status' => 'active', 'department' => ['id' => 'd1', 'code' => 'CSE', 'name' => 'Computer Science']], $overrides);
    }

    public function test_course_list_create_and_syllabus_editor(): void
    {
        $detail = $this->course() + ['description' => null, 'textbooks' => ['Elmasri'], 'units' => [['id' => 'u1', 'number' => 1, 'title' => 'Introduction', 'hours' => 8, 'topics' => [['id' => 'tp1', 'title' => 'Database concepts', 'hours' => 1.0], ['id' => 'tp2', 'title' => 'ER model', 'hours' => 2.0]]]]];
        Http::fake([
            self::API.'courses/c1/syllabus' => Http::response(['data' => $detail]),
            self::API.'courses/c1' => Http::response(['data' => $detail]),
            self::API.'courses*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $detail], 201) : Http::response(['data' => [$this->course()], 'meta' => ['total' => 1]]),
        ]);

        Livewire::test(Courses::class)
            ->assertSee('Database Management Systems')
            ->assertSee('3-1-0')
            ->call('openCreate')
            ->set('form.department_id', 'd1')->set('form.code', '21CSL55')->set('form.title', 'DBMS Lab')->set('form.type', 'lab')->set('form.credits', '1')->set('form.practical_hours', '2')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['code'] === '21CSL55' && $r['practical_hours'] === 2 && $r['credits'] === 1.0);

        Livewire::test(CourseShow::class, ['course' => 'c1'])
            ->assertSee('Unit 1: Introduction')
            ->assertSee('ER model')
            ->call('editSyllabus')
            ->assertSet('units.0.topics', "Database concepts\nER model")
            ->call('addUnit')
            ->set('units.1.title', 'Relational model')->set('units.1.topics', "Relational algebra\n\n  SQL basics  ")
            ->call('saveSyllabus')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'c1/syllabus') && $r['units'][1]['topics'] === [['title' => 'Relational algebra'], ['title' => 'SQL basics']] && $r['textbooks'] === ['Elmasri']);
    }

    public function test_curriculum_editing_submission_and_decision(): void
    {
        $state = 'draft';
        $entries = [['term_number' => 1, 'category' => 'core', 'is_elective' => false, 'course' => $this->course()]];
        Http::fake([
            self::API.'courses*' => Http::response(['data' => [$this->course(), $this->course(['id' => 'c2', 'code' => '21CSL55', 'title' => 'DBMS Lab'])], 'meta' => ['total' => 2]]),
            self::API.'curricula/cu1*' => function (Request $r) use (&$state, $entries) {
                if (str_ends_with($r->url(), '/submit')) {
                    $state = 'submitted';
                }

                return Http::response(['data' => ['id' => 'cu1', 'programme' => ['id' => 'p1', 'code' => 'BE-CSE', 'name' => 'B.E. Computer Science'], 'total_terms' => 8, 'regulation' => '2021', 'version' => 1, 'status' => $state, 'remarks' => null, 'approved_by' => null, 'approved_at' => null, 'courses_count' => 1, 'entries' => $entries, 'credits_by_term' => [1 => 4.0]]]);
            },
        ]);

        Livewire::test(CurriculumShow::class, ['curriculum' => 'cu1'])
            ->assertSee('Term 1')
            ->assertSee('Professional core')
            ->call('openAdd', 1)
            ->set('entry.course_id', 'c2')->set('entry.category', 'lab')
            ->call('addEntry')
            ->assertHasNoErrors()
            ->call('submit')
            ->assertSee('Submitted');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'cu1/entries') && count($r['entries']) === 2 && $r['entries'][1] === ['course_id' => 'c2', 'term_number' => 1, 'category' => 'lab', 'is_elective' => false]);

        $this->signIn(['permissions' => ['academics.catalogue.view', 'academics.curriculum.approve'], 'roles' => ['institution_head']]);
        Livewire::test(CurriculumShow::class, ['curriculum' => 'cu1'])
            ->call('decide', false)
            ->assertHasErrors(['remarks'])
            ->set('remarks', 'Add an elective')
            ->call('decide', false)
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'cu1/decide') && $r['approve'] === false && $r['remarks'] === 'Add an elective');
    }

    public function test_offerings_generate_and_allocate_with_workload_warning(): void
    {
        $offering = ['id' => 'o1', 'course' => $this->course(), 'term' => ['id' => 't1', 'name' => 'Odd 2026', 'is_current' => true], 'is_elective' => false, 'capacity' => null, 'status' => 'active', 'sections' => [['id' => 'os1', 'section' => ['id' => 's1', 'name' => 'A', 'batch' => 'BE-CSE 2026'], 'faculty' => [], 'coverage_percent' => 0]]];
        Http::fake([
            self::API.'course-offerings/generate' => Http::response(['data' => ['created' => 2, 'skipped' => 0, 'message' => '2 course(s) offered, 0 already offered.']]),
            self::API.'course-offerings*' => Http::response(['data' => [$offering]]),
            self::API.'offering-sections/os1/allocations' => Http::response(['data' => ['id' => 'fa1', 'warning' => 'Dr. Kavya now teaches 20 hours a week (limit 18).']], 201),
            self::API.'courses*' => Http::response(['data' => [$this->course()], 'meta' => ['total' => 1]]),
            self::API.'batches*' => Http::response(['data' => [['id' => 'b1', 'name' => 'BE-CSE 2026', 'sections' => [['id' => 's1', 'name' => 'A'], ['id' => 's2', 'name' => 'B']]]]]),
            self::API.'curricula*' => Http::response(['data' => [['id' => 'cu1', 'programme' => ['name' => 'B.E. Computer Science'], 'regulation' => '2021', 'version' => 1]]]),
            self::API.'employees*' => Http::response(['data' => [['id' => 'e1', 'name' => 'Dr. Kavya', 'employee_code' => 'EMP-1', 'department' => ['name' => 'CSE']]], 'meta' => ['total' => 1]]),
        ]);

        Livewire::test(Offerings::class, ['generate' => 'cu1'])
            ->assertSet('generateForm.curriculum_id', 'cu1')
            ->assertSee('No teacher')
            ->set('generateForm.term_number', '5')->set('generateForm.section_ids', ['s1', 's2'])
            ->call('generate')
            ->assertHasNoErrors()
            ->call('openAllocate', 'os1')
            ->set('allocation.employee_id', 'e1')->set('allocation.weekly_hours', '4')
            ->call('allocate')
            ->assertHasNoErrors()
            ->assertDispatched('toast', fn (string $event, array $params) => ($params['type'] ?? $params[0]['type'] ?? null) === 'warning' || str_contains(json_encode($params), 'limit 18'));

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'course-offerings/generate') && $r['term_id'] === 't1' && $r['term_number'] === 5 && $r['section_ids'] === ['s1', 's2']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'os1/allocations') && $r['component'] === 'theory' && $r['weekly_hours'] === 4);
    }

    public function test_workload_report(): void
    {
        Http::fake([self::API.'faculty-workload*' => Http::response(['data' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'limit' => 18, 'faculty' => [['employee_id' => 'e1', 'name' => 'Dr. Kavya', 'department' => 'Computer Science', 'weekly_hours' => 20, 'over_limit' => true, 'classes' => [['course' => '21CS51', 'section' => 'A', 'component' => 'theory', 'weekly_hours' => 4]]]]]])]);

        Livewire::test(Workload::class)->assertSee('Dr. Kavya')->assertSee('Over limit')->assertSee('21CS51 A');
    }

    public function test_teacher_marks_topics_and_shares_material_student_reads(): void
    {
        $class = fn (bool $teach) => ['id' => 'os1', 'course' => $this->course(), 'term' => ['id' => 't1', 'name' => 'Odd 2026'], 'section' => ['name' => 'A', 'batch' => 'BE-CSE 2026'], 'faculty' => [['id' => 'fa1', 'employee_id' => 'e1', 'name' => 'Dr. Kavya', 'component' => 'theory', 'weekly_hours' => 4]], 'can_teach' => $teach, 'coverage_percent' => 50,
            'units' => [['id' => 'u1', 'number' => 1, 'title' => 'Introduction', 'topics' => [['id' => 'tp1', 'title' => 'Database concepts', 'hours' => 1.0, 'covered_on' => '2026-10-20', 'covered_by' => 'Dr. Kavya', 'notes' => null], ['id' => 'tp2', 'title' => 'ER model', 'hours' => 2.0, 'covered_on' => null, 'covered_by' => null, 'notes' => null]]]],
            'materials' => [['id' => 'm1', 'title' => 'Unit 1 notes', 'unit' => ['id' => 'u1', 'number' => 1, 'title' => 'Introduction'], 'file_id' => '01JBXFILE00000000000000001', 'file_name' => 'u1.pdf', 'url' => null, 'is_published' => true, 'created_at' => now()->toIso8601String()]]];

        Http::fake([
            self::API.'offering-sections/os1/coverage*' => Http::response(['data' => $class(true)]),
            self::API.'offering-sections/os1/materials' => Http::response(['data' => $class(true)]),
            self::API.'offering-sections/os1/students' => Http::response(['data' => ['class' => ['id' => 'os1'], 'students' => [['student_id' => 'st1', 'register_no' => 'R1', 'roll_no' => '01', 'name' => 'Asha', 'registration_status' => 'submitted']]]]),
            self::API.'offering-sections/os1' => Http::response(['data' => $class(true)]),
            self::API.'me/teaching' => Http::response(['data' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'weekly_hours' => 4, 'classes' => [['id' => 'os1', 'course' => $this->course(), 'section' => ['name' => 'A', 'batch' => 'BE-CSE 2026'], 'components' => [['component' => 'theory', 'weekly_hours' => 4]], 'coverage_percent' => 50]]]]),
        ]);

        Livewire::test(MyClasses::class)->assertSee('Database Management Systems')->assertSee('Theory 4h');

        Livewire::test(ClassShow::class, ['class' => 'os1'])
            ->assertSee('Taught')
            ->assertSee('Asha')
            ->assertSee('Pending')
            ->call('toggleTopic', 'tp2', true)
            ->call('toggleTopic', 'tp1', false)
            ->call('openMaterial')
            ->set('material.title', 'Quiz')
            ->call('addMaterial')
            ->assertHasErrors(['material.url'])
            ->set('material.url', 'https://example.edu/quiz')->set('material.is_published', false)
            ->call('addMaterial')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'os1/coverage') && $r['topic_id'] === 'tp2');
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), 'os1/coverage/tp1'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'os1/materials') && $r['url'] === 'https://example.edu/quiz' && $r['is_published'] === false);
    }

    public function test_student_sees_courses_without_edit_controls(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['student']]);
        Http::fake([
            self::API.'me/courses' => Http::response(['data' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'courses' => [['id' => 'os1', 'course' => $this->course(), 'faculty' => [['name' => 'Dr. Kavya', 'component' => 'theory']], 'coverage_percent' => 50]]]]),
            self::API.'offering-sections/os1' => Http::response(['data' => ['id' => 'os1', 'course' => $this->course(), 'term' => ['id' => 't1', 'name' => 'Odd 2026'], 'section' => ['name' => 'A', 'batch' => 'BE-CSE 2026'], 'faculty' => [], 'can_teach' => false, 'coverage_percent' => 0, 'units' => [], 'materials' => []]]),
        ]);

        Livewire::test(MyClasses::class)->assertSee('My courses')->assertSee('Dr. Kavya')->assertDontSee('Teaching');
        Livewire::test(ClassShow::class, ['class' => 'os1'])->assertDontSee('Share material');
    }

    public function test_student_registers_with_electives(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['student']]);
        $registered = false;
        Http::fake([self::API.'me/term-registration' => function (Request $r) use (&$registered) {
            $registered = $registered || $r->method() === 'POST';

            return Http::response(['data' => [
                'term' => ['id' => 't1', 'name' => 'Odd 2026'], 'credit_limit' => 8.0, 'can_register' => ! $registered,
                'registration' => $registered ? ['id' => 'r1', 'status' => 'submitted', 'total_credits' => 7.0, 'remarks' => null, 'submitted_at' => now()->toIso8601String(), 'decided_by' => null, 'courses' => [['offering_id' => 'o1', 'code' => '21CS51', 'title' => 'DBMS', 'credits' => 4.0, 'type' => 'core'], ['offering_id' => 'o2', 'code' => '21CS561', 'title' => 'Artificial Intelligence', 'credits' => 3.0, 'type' => 'elective']]] : null,
                'core' => [['offering_id' => 'o1', 'course' => $this->course()]],
                'electives' => [
                    ['offering_id' => 'o2', 'course' => $this->course(['id' => 'c2', 'code' => '21CS561', 'title' => 'Artificial Intelligence', 'credits' => 3.0]), 'capacity' => 60, 'seats_left' => 12],
                    ['offering_id' => 'o3', 'course' => $this->course(['id' => 'c3', 'code' => '21CS562', 'title' => 'Internet of Things', 'credits' => 3.0]), 'capacity' => 60, 'seats_left' => 0],
                ],
            ]]);
        }]);

        Livewire::test(MyRegistration::class)
            ->assertSee('Core courses')
            ->assertSee('12 seats left')
            ->assertSee('Full')
            ->set('electives', ['o2'])
            ->assertSee('Total: 7 credits')
            ->call('register')
            ->assertHasNoErrors()
            ->assertSee('Waiting for approval');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['elective_offering_ids'] === ['o2']);
    }

    public function test_staff_approve_and_reject_registrations(): void
    {
        $this->signIn(['permissions' => ['academics.registrations.approve'], 'roles' => ['hod']]);
        $row = ['id' => 'r1', 'student' => ['id' => 'st1', 'name' => 'Asha', 'register_no' => 'R1', 'programme' => 'B.E.'], 'term' => ['id' => 't1', 'name' => 'Odd 2026'], 'section' => ['name' => 'A', 'batch' => 'BE-CSE 2026'], 'term_number' => 5, 'status' => 'submitted', 'total_credits' => 7.0, 'remarks' => null, 'submitted_at' => now()->toIso8601String(), 'decided_by' => null, 'decided_at' => null, 'courses' => [['offering_id' => 'o1', 'code' => '21CS51', 'title' => 'DBMS', 'credits' => 4.0, 'type' => 'core']]];
        Http::fake([
            self::API.'programmes*' => Http::response(['data' => []]),
            self::API.'term-registrations/decide' => Http::response(['data' => ['decided' => 1, 'message' => '1 registration(s) approved.']]),
            self::API.'term-registrations*' => Http::response(['data' => [$row, ['id' => 'r2'] + $row], 'meta' => ['total' => 2]]),
        ]);

        Livewire::test(Registrations::class)
            ->assertSee('Asha')
            ->set('selected', ['r1', 'r2'])
            ->call('approve')
            ->assertSet('selected', [])
            ->call('askReject', 'r1')
            ->call('reject')
            ->assertHasErrors(['remarks'])
            ->set('remarks', 'Choose one elective')
            ->call('reject')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'decide') && $r['ids'] === ['r1', 'r2'] && $r['approve'] === true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'decide') && $r['ids'] === ['r1'] && $r['approve'] === false && $r['remarks'] === 'Choose one elective');
    }

    public function test_course_pages_need_permission(): void
    {
        $this->fakeShell();

        $this->signIn(['permissions' => ['identity.users.view'], 'roles' => ['applicant']])->get(route('courses.index'))->assertForbidden();
    }
}
