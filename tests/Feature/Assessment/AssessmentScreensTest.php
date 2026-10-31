<?php

namespace Tests\Feature\Assessment;

use App\Livewire\Assessment\Approvals;
use App\Livewire\Assessment\ClassAssignments;
use App\Livewire\Assessment\ClassMarks;
use App\Livewire\Assessment\MyAssignments;
use App\Livewire\Assessment\MyMarks;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['assessment.marks.enter', 'assessment.marks.verify', 'assessment.marks.lock', 'assessment.corrections.approve', 'assessment.schemes.manage'], 'roles' => ['hod']]);
        $labels = fn (array $values) => array_map(fn (string $v) => ['value' => $v, 'label' => ucfirst($v)], $values);
        Http::fake([self::API.'assessment/options' => Http::response(['data' => [
            'kinds' => $labels(['test', 'quiz', 'assignment']), 'mark_statuses' => $labels(['present', 'absent', 'exempt']),
            'sheet_statuses' => $labels(['draft', 'submitted', 'verified', 'locked']), 'bloom_levels' => $labels(['remember', 'apply']),
            'round_internal' => true, 'co_target_percent' => 60.0,
        ]])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sheet(string $status = 'draft', bool $computed = false): array
    {
        return [
            'id' => 'sh1', 'class' => ['id' => 'os1', 'offering_id' => 'of1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A'],
            'status' => $status, 'remarks' => null, 'computed_at' => $computed ? now()->toIso8601String() : null,
            'rules' => $computed ? ['groups' => [['name' => 'IA tests', 'weight' => 30.0]], 'total' => 30.0] : null,
            'submitted_by' => null, 'submitted_at' => null, 'verified_by' => null, 'verified_at' => null,
            'can_edit' => $status === 'draft', 'can_verify' => $status === 'submitted', 'can_lock' => $status === 'verified',
            'students' => [['id' => 'st1', 'name' => 'Asha', 'roll_no' => '01', 'register_no' => 'R1', 'computed' => $computed ? 25.5 : null, 'final' => $computed ? 26.0 : null, 'breakdown' => $computed ? [['group' => 'IA tests', 'score' => 25.5]] : null]],
        ];
    }

    private function fakeClass(string $status = 'draft'): void
    {
        Http::fake([
            self::API.'classes/os1/internal-marks*' => fn (Request $r) => Http::response(['data' => $this->sheet($r->method() === 'POST' && str_ends_with($r->url(), 'submit') ? 'submitted' : $status, $r->method() === 'POST')]),
            self::API.'offerings/of1/scheme' => Http::response(['data' => [
                'offering' => ['id' => 'of1', 'course' => ['id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS']], 'can_manage' => true, 'total_weight' => 30.0,
                'groups' => [['id' => 'g1', 'name' => 'IA tests', 'best_of' => 2, 'weight' => 30.0, 'position' => 1, 'assessments' => [['id' => 'a1', 'name' => 'IA1', 'kind' => 'test', 'held_on' => null, 'max_marks' => 50.0, 'outcome_codes' => ['CO1']]]]],
            ]]),
            self::API.'assessments/a1/marks*' => fn (Request $r) => $r->method() === 'PUT'
                ? Http::response(['message' => 'Marks saved.'])
                : Http::response(['data' => [
                    'assessment' => ['id' => 'a1', 'name' => 'IA1', 'kind' => 'test', 'held_on' => null, 'max_marks' => 50.0, 'outcome_codes' => []],
                    'class' => [], 'editable' => $status === 'draft', 'sheet_status' => $status,
                    'students' => [['id' => 'st1', 'name' => 'Asha', 'roll_no' => '01', 'register_no' => 'R1', 'mark_id' => $status === 'draft' ? null : 9, 'marks' => $status === 'draft' ? null : 40.0, 'status' => $status === 'draft' ? null : 'present']],
                ]]),
            self::API.'courses/c1/outcomes' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'co2']], 201) : Http::response(['data' => [['id' => 'co1', 'code' => 'CO1', 'description' => 'Design ER models', 'bloom_level' => 'apply']]]),
            self::API.'classes/os1/attainment' => Http::response(['data' => ['class' => [], 'outcomes' => [['code' => 'CO1', 'description' => 'Design ER models', 'assessments' => 3, 'students' => 2, 'attained' => 1, 'percentage' => 50.0]]]]),
            self::API.'mark-corrections' => Http::response(['data' => ['id' => 'mc1']], 201),
        ]);
    }

    public function test_teacher_enters_marks_and_submits_internal_marks(): void
    {
        $this->fakeClass();

        Livewire::test(ClassMarks::class, ['class' => 'os1'])
            ->assertSee('IA1')
            ->assertSet('assessmentId', 'a1')
            ->assertSet('entries', ['st1' => ['marks' => null, 'status' => 'present']])
            ->set('entries.st1.marks', '42.5')
            ->call('saveMarks')
            ->assertHasNoErrors()
            ->set('tab', 'internal')
            ->assertSee('Not calculated yet')
            ->call('compute')
            ->call('submit');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['class'] === 'os1' && $r['marks'] === ['st1' => ['marks' => 42.5, 'status' => 'present']]);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'internal-marks/submit'));
    }

    public function test_scheme_outcomes_and_attainment(): void
    {
        $this->fakeClass();
        Http::fake([self::API.'offerings/of1/assessment-groups' => Http::response(['data' => ['id' => 'g2']], 201)]);

        Livewire::test(ClassMarks::class, ['class' => 'os1'])
            ->set('tab', 'scheme')
            ->assertSee('best 2')
            ->call('openGroup')
            ->set('group.name', 'Assignments')
            ->set('group.weight', '10')
            ->call('saveGroup')
            ->assertHasNoErrors()
            ->set('tab', 'outcomes')
            ->assertSee('Design ER models')
            ->assertSee('1 of 2 students')
            ->set('outcome.code', 'CO2')
            ->set('outcome.description', 'Write SQL')
            ->call('addOutcome')
            ->assertSet('outcome.code', '');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'offerings/of1/assessment-groups') && $r['name'] === 'Assignments' && $r['weight'] === 10.0 && $r['best_of'] === null);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'courses/c1/outcomes') && $r['code'] === 'CO2');
    }

    public function test_submitted_marks_are_read_only_and_corrections_can_be_requested(): void
    {
        $this->fakeClass('submitted');

        Livewire::test(ClassMarks::class, ['class' => 'os1'])
            ->assertSee('ask for a correction')
            ->assertDontSee('Save marks')
            ->call('openCorrection', 9, 'Asha', 40.0, 'present')
            ->set('correction.marks', '44')
            ->set('correction.reason', 'Totalling error')
            ->call('requestCorrection')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'mark-corrections') && $r['mark_id'] === 9 && $r['marks'] === 44.0);
    }

    public function test_approvals_verify_sheets_and_decide_corrections(): void
    {
        Http::fake([
            self::API.'internal-mark-sheets?*' => Http::response(['data' => [['id' => 'sh1', 'class' => ['id' => 'os1', 'offering_id' => 'of1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'A'], 'status' => 'submitted', 'students' => 60, 'submitted_by' => 'Dr. Kavya', 'submitted_at' => now()->toIso8601String(), 'can_verify' => true, 'can_lock' => false]]]),
            self::API.'internal-mark-sheets/sh1/verify' => Http::response(['message' => 'ok']),
            self::API.'mark-corrections?*' => Http::response(['data' => [['id' => 'mc1', 'assessment' => 'IA1', 'student' => ['id' => 'st1', 'name' => 'Asha', 'roll_no' => '01', 'register_no' => 'R1'], 'current' => ['marks' => 40.0, 'status' => 'present'], 'requested' => ['marks' => null, 'status' => 'absent'], 'reason' => 'Was absent', 'status' => 'pending', 'remarks' => null, 'requested_by' => 'Dr. Kavya', 'requested_at' => now()->toIso8601String()]], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]]),
            self::API.'mark-corrections/mc1/decide' => Http::response(['message' => 'ok']),
        ]);

        Livewire::test(Approvals::class)
            ->assertSee('Dr. Kavya')
            ->call('verify', 'sh1')
            ->set('tab', 'corrections')
            ->assertSeeInOrder(['40', 'Absent'])
            ->call('askReject', 'mc1')
            ->call('reject')
            ->assertHasErrors('remarks')
            ->set('remarks', 'Was present')
            ->call('reject')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'sh1/verify') && $r['approve'] === true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'mc1/decide') && $r['approve'] === false && $r['remarks'] === 'Was present');
    }

    public function test_teacher_creates_and_evaluates_assignments(): void
    {
        $this->fakeClass();
        Http::fake([
            self::API.'offerings/of1/assignments' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'as1']], 201) : Http::response(['data' => [['id' => 'as1', 'title' => 'ER diagram', 'instructions' => 'Draw it', 'file' => null, 'due_at' => now()->addDay()->toIso8601String(), 'max_marks' => 20.0, 'allow_late' => false, 'assessment' => null, 'submissions' => 1, 'evaluated' => 0]]]),
            self::API.'assignments/as1/submissions' => Http::response(['data' => ['assignment' => ['id' => 'as1', 'title' => 'ER diagram', 'instructions' => 'Draw it', 'max_marks' => 20.0], 'submissions' => [['id' => 'sb1', 'student' => ['id' => 'st1', 'name' => 'Asha', 'roll_no' => '01', 'register_no' => 'R1'], 'text' => 'My ER', 'file' => null, 'submitted_at' => now()->toIso8601String(), 'is_late' => false, 'marks' => null, 'feedback' => null, 'evaluated_at' => null]]]]),
            self::API.'assignment-submissions/sb1/evaluate' => Http::response(['message' => 'ok']),
        ]);

        Livewire::test(ClassAssignments::class, ['class' => 'os1'])
            ->assertSee('1 submitted · 0 evaluated')
            ->call('open')
            ->set('form.title', 'Normalisation')
            ->set('form.due_at', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.max_marks', '10')
            ->set('form.assessment_id', 'a1')
            ->call('save')
            ->assertHasNoErrors()
            ->set('assignmentId', 'as1')
            ->assertSee('My ER')
            ->call('openEvaluation', 'sb1', 'Asha', null, null)
            ->set('evaluation.marks', '15')
            ->call('evaluate')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'offerings/of1/assignments') && $r['title'] === 'Normalisation' && $r['assessment_id'] === 'a1' && $r['allow_late'] === false);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'sb1/evaluate') && $r['marks'] === 15.0);
    }

    public function test_student_sees_marks_and_submits_an_assignment(): void
    {
        Http::fake([
            self::API.'me/internal-marks' => Http::response(['data' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'courses' => [['class_id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'assessments' => [['name' => 'IA1', 'max_marks' => 50.0, 'marks' => 40.0, 'status' => 'present'], ['name' => 'IA2', 'max_marks' => 50.0, 'marks' => null, 'status' => 'absent']], 'internal' => ['final' => 33.0, 'out_of' => 40.0, 'breakdown' => []]]]]]),
            self::API.'me/assignments' => Http::response(['data' => [['id' => 'as1', 'title' => 'ER diagram', 'instructions' => null, 'file' => null, 'due_at' => now()->addDay()->toIso8601String(), 'max_marks' => 20.0, 'allow_late' => false, 'assessment' => null, 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'can_submit' => true, 'submission' => null]]]),
            self::API.'me/assignments/as1/submit' => Http::response(['data' => ['id' => 'sb1', 'is_late' => false]], 201),
        ]);

        Livewire::test(MyMarks::class)->assertSeeInOrder(['DBMS', '33', '/ 40', 'IA1', '40', 'IA2', 'Absent']);

        Livewire::test(MyAssignments::class)
            ->assertSee('ER diagram')
            ->call('open', 'as1', 'ER diagram', null)
            ->call('submit')
            ->assertHasErrors('text')
            ->set('text', 'My answer')
            ->call('submit')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'as1/submit') && $r['text'] === 'My answer');
    }
}
