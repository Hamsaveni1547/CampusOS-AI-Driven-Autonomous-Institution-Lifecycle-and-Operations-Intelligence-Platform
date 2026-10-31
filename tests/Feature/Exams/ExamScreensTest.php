<?php

namespace Tests\Feature\Exams;

use App\Livewire\Exams\Cycles;
use App\Livewire\Exams\CycleShow;
use App\Livewire\Exams\HallTicket;
use App\Livewire\Exams\MarksEntry;
use App\Livewire\Exams\MyExams;
use App\Livewire\Exams\MyResults;
use App\Livewire\Exams\Revaluations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class ExamScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['exams.cycles.manage', 'exams.eligibility.override', 'exams.evaluation.enter', 'exams.scripts.decode', 'exams.results.process', 'exams.revaluation.manage', 'exams.reports.view'], 'roles' => ['examiner']]);
        $labels = fn (array $values) => array_map(fn (string $v) => ['value' => $v, 'label' => ucfirst(str_replace('_', ' ', $v))], $values);
        Http::fake([self::API.'exams/options' => Http::response(['data' => [
            'types' => $labels(['regular', 'supplementary']),
            'statuses' => $labels(['draft', 'registration_open', 'registration_closed', 'results_processed', 'results_verified', 'published']),
            'external_pass_percent' => 35.0, 'overall_pass_percent' => 40.0, 'grades' => [['grade' => 'O', 'min' => 90.0, 'points' => 10.0]],
        ]])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cycle(string $status): array
    {
        return [
            'id' => 'cy1', 'name' => 'Nov 2026', 'type' => 'regular', 'status' => $status, 'term' => ['id' => 't1', 'name' => 'Odd 2026'],
            'registration_ends_on' => null, 'registrations' => 2, 'published_at' => null, 'processed_by' => null, 'processed_at' => null, 'verified_by' => null,
            'eligibility' => ['eligible' => 1, 'not_eligible' => 1, 'pending' => 0],
            'schedules' => [['id' => 'sc1', 'course' => ['id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS'], 'exam_date' => '2026-11-20', 'starts_at' => '10:00', 'ends_at' => '13:00', 'max_marks' => 60.0, 'candidates' => 1]],
            'can' => ['manage' => true, 'process' => $status === 'registration_closed', 'verify' => false, 'publish' => false, 'enter_marks' => $status === 'registration_closed'],
        ];
    }

    public function test_create_a_cycle(): void
    {
        Http::fake([
            self::API.'exam-cycles?*' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 0]]),
            self::API.'exam-cycles' => Http::response(['data' => ['id' => 'cy1']], 201),
            self::API.'terms*' => Http::response(['data' => [['id' => 't1', 'name' => 'Odd 2026', 'is_current' => true, 'starts_on' => '2026-08-01']]]),
        ]);

        Livewire::test(Cycles::class)
            ->assertSee('No exam cycles yet')
            ->call('create')
            ->assertSet('form.term_id', 't1')
            ->set('form.name', 'Nov 2026')
            ->call('save')
            ->assertRedirect(route('exam-cycles.show', 'cy1'));

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'exam-cycles') && $r['name'] === 'Nov 2026' && $r['type'] === 'regular');
    }

    public function test_cycle_steps_timetable_and_override(): void
    {
        Http::fake([
            self::API.'exam-cycles/cy1' => Http::response(['data' => $this->cycle('registration_closed')]),
            self::API.'exam-cycles/cy1/process' => Http::response(['data' => $this->cycle('results_processed')]),
            self::API.'exam-cycles/cy1/schedules' => Http::response(['data' => ['id' => 'sc2']], 201),
            self::API.'exam-cycles/cy1/registrations*' => Http::response(['data' => [[
                'id' => 'rg1', 'student' => ['id' => 'st1', 'name' => 'Bala', 'register_no' => 'R2'], 'course' => ['id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS', 'credits' => 4.0],
                'kind' => 'regular', 'eligibility' => 'not_eligible', 'reasons' => ['Attendance below the minimum'], 'override_reason' => null, 'attendance' => null,
                'internal' => 10.0, 'internal_out_of' => 40.0, 'external' => null, 'total_percent' => null, 'grade' => null, 'grade_points' => null, 'passed' => null, 'revision' => 0, 'script_code' => null,
            ]], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 1]]),
            self::API.'exam-registrations/rg1/override' => Http::response(['message' => 'ok']),
            self::API.'courses*' => Http::response(['data' => [['id' => 'c2', 'code' => '21CS52', 'title' => 'OS']], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 200, 'total' => 1]]),
        ]);

        Livewire::test(CycleShow::class, ['cycle' => 'cy1'])
            ->assertSee('21CS51')
            ->assertSee('Process results')
            ->call('step', 'process')
            ->call('openSchedule')
            ->set('schedule.course_id', 'c2')
            ->set('schedule.exam_date', '2026-11-22')
            ->call('saveSchedule')
            ->assertHasNoErrors()
            ->set('tab', 'registrations')
            ->assertSee('Attendance below the minimum')
            ->call('askOverride', 'rg1')
            ->set('reason', 'Medical')
            ->call('override')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'cy1/process'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'cy1/schedules') && $r['course_id'] === 'c2' && $r['max_marks'] === 60.0 && $r['starts_at'] === '10:00');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'rg1/override') && $r['reason'] === 'Medical');
    }

    public function test_marks_are_entered_against_script_codes(): void
    {
        Http::fake([self::API.'exam-schedules/sc1/*' => fn (Request $r) => $r->method() === 'PUT'
            ? Http::response(['message' => 'ok'])
            : Http::response(['data' => [
                'id' => 'sc1', 'course' => ['id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS'], 'exam_date' => '2026-11-20', 'starts_at' => '10:00', 'ends_at' => '13:00', 'max_marks' => 60.0, 'candidates' => 2,
                'editable' => true, 'scripts' => [['code' => '11111111', 'attendance' => null, 'marks' => null], ['code' => '22222222', 'attendance' => null, 'marks' => null]],
            ]])]);

        Livewire::test(MarksEntry::class, ['schedule' => 'sc1'])
            ->assertSee('11111111')
            ->assertDontSee('Bala')
            ->set('entries.11111111.marks', '45')
            ->set('entries.22222222.attendance', 'absent')
            ->call('save');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['marks'] === ['11111111' => ['attendance' => 'present', 'marks' => 45.0], '22222222' => ['attendance' => 'absent']]);
    }

    public function test_revaluation_is_decided_with_revised_marks(): void
    {
        Http::fake([
            self::API.'revaluation-requests?*' => Http::response(['data' => [['id' => 'rv1', 'cycle' => 'Nov 2026', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'student' => ['name' => 'Asha', 'register_no' => 'R1'], 'script_code' => '11111111', 'marks' => 45.0, 'grade' => 'A', 'reason' => 'Expected more', 'status' => 'pending', 'old_marks' => null, 'new_marks' => null, 'remarks' => null, 'requested_at' => now()->toIso8601String()]], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1]]),
            self::API.'revaluation-requests/rv1/decide' => Http::response(['message' => 'ok']),
        ]);

        Livewire::test(Revaluations::class)
            ->assertSee('Expected more')
            ->call('open', 'rv1', 'Asha', 45.0)
            ->call('decide')
            ->assertHasErrors('decision.new_marks')
            ->set('decision.new_marks', '55')
            ->call('decide')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'rv1/decide') && $r['new_marks'] === 55.0);
    }

    public function test_student_registers_backlogs_sees_hall_ticket_and_results(): void
    {
        Http::fake([
            self::API.'me/exams' => Http::response(['data' => ['cycles' => [['id' => 'cy2', 'name' => 'Supplementary Dec 2026', 'status' => 'registration_open', 'registration_ends_on' => null, 'hall_ticket' => false, 'courses' => [], 'backlogs_open' => [['course_id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS', 'grade' => 'AB']]]]]]),
            self::API.'me/exam-cycles/cy2/backlogs' => Http::response(['message' => 'ok']),
            self::API.'me/hall-ticket/cy1' => Http::response(['data' => ['cycle' => ['id' => 'cy1', 'name' => 'Nov 2026'], 'student' => ['name' => 'Asha', 'register_no' => 'R1', 'programme' => 'B.E. CSE', 'photo_id' => null], 'exams' => [['code' => '21CS51', 'title' => 'DBMS', 'exam' => ['date' => '2026-11-20', 'starts_at' => '10:00', 'ends_at' => '13:00']]]]]),
            self::API.'me/results' => Http::response(['data' => ['cgpa' => 8.0, 'backlogs' => 0, 'results' => [['cycle' => ['id' => 'cy1', 'name' => 'Nov 2026', 'published_at' => null], 'sgpa' => 8.0, 'cgpa' => 8.0, 'credits_earned' => 4.0, 'credits_registered' => 4.0, 'courses' => [['id' => 'rg1', 'code' => '21CS51', 'title' => 'DBMS', 'credits' => 4.0, 'internal' => 33.0, 'external' => 45.0, 'grade' => 'A', 'passed' => true, 'revaluation' => null, 'can_request_revaluation' => true]]]]]]),
            self::API.'me/revaluation-requests' => Http::response(['data' => ['id' => 'rv1']], 201),
        ]);

        Livewire::test(MyExams::class)
            ->assertSee('Last grade: AB')
            ->set('backlogs.cy2', ['c1'])
            ->call('registerBacklogs', 'cy2');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'cy2/backlogs') && $r['course_ids'] === ['c1']);

        Livewire::test(HallTicket::class, ['cycle' => 'cy1'])->assertSeeInOrder(['Asha', 'R1', '21CS51', '10:00']);

        Livewire::test(MyResults::class)
            ->assertSeeInOrder(['8', 'DBMS', 'A'])
            ->call('openRevaluation', 'rg1', '21CS51')
            ->set('request.reason', 'Expected more')
            ->call('requestRevaluation')
            ->assertHasNoErrors();
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'me/revaluation-requests') && $r['registration_id'] === 'rg1');
    }
}
