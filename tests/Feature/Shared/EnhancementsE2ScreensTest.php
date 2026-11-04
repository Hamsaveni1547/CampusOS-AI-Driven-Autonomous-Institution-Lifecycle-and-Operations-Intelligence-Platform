<?php

namespace Tests\Feature\Shared;

use App\Livewire\Assessment\ClassMarks;
use App\Livewire\Feedback\MyComplaints;
use App\Livewire\Timetable\MyTimetable;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class EnhancementsE2ScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_marks_sheet_upload_fills_the_grid(): void
    {
        $this->signIn(['permissions' => ['assessment.marks.enter'], 'roles' => ['faculty']]);
        $labels = fn (array $values) => array_map(fn (string $v) => ['value' => $v, 'label' => ucfirst($v)], $values);
        Http::fake([
            self::API.'assessment/options' => Http::response(['data' => ['kinds' => $labels(['test']), 'mark_statuses' => $labels(['present', 'absent', 'exempt']), 'sheet_statuses' => $labels(['draft']), 'bloom_levels' => $labels(['apply']), 'round_internal' => true, 'co_target_percent' => 60.0]]),
            self::API.'classes/os1/internal-marks*' => Http::response(['data' => ['id' => 'sh1', 'class' => ['id' => 'os1', 'offering_id' => 'of1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A'], 'computed_at' => null, 'rules' => null, 'status' => 'draft', 'remarks' => null, 'submitted_by' => null, 'submitted_at' => null, 'verified_by' => null, 'verified_at' => null, 'can_edit' => true, 'can_verify' => false, 'can_lock' => false, 'students' => []]]),
            self::API.'offerings/of1/scheme' => Http::response(['data' => ['offering' => ['id' => 'of1', 'course' => ['id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS']], 'can_manage' => true, 'total_weight' => 30.0,
                'groups' => [['id' => 'g1', 'name' => 'IA tests', 'best_of' => 2, 'weight' => 30.0, 'position' => 1, 'assessments' => [['id' => 'a1', 'name' => 'IA1', 'kind' => 'test', 'held_on' => null, 'max_marks' => 50.0, 'outcome_codes' => []]]]]]]),
            self::API.'assessments/a1/marks*' => Http::response(['data' => ['assessment' => ['id' => 'a1', 'name' => 'IA1', 'kind' => 'test', 'held_on' => null, 'max_marks' => 50.0, 'outcome_codes' => []], 'class' => [], 'editable' => true, 'sheet_status' => 'draft',
                'students' => [
                    ['id' => 'st1', 'name' => 'Asha', 'roll_no' => '01', 'register_no' => 'R1', 'mark_id' => null, 'marks' => null, 'status' => null],
                    ['id' => 'st2', 'name' => 'Bala', 'roll_no' => '02', 'register_no' => 'R2', 'mark_id' => null, 'marks' => null, 'status' => null],
                ]]]),
        ]);

        $csv = "register_no,name,marks\nR1,Asha,42.5\nr2,Bala,AB\nR9,Nobody,10\n";

        Livewire::test(ClassMarks::class, ['class' => 'os1'])
            ->assertSee('Upload CSV')
            ->set('marksCsv', UploadedFile::fake()->createWithContent('marks.csv', $csv))
            ->assertSet('entries.st1', ['marks' => 42.5, 'status' => 'present'])
            ->assertSet('entries.st2', ['marks' => null, 'status' => 'absent'])
            ->call('downloadMarksTemplate')
            ->assertFileDownloaded('marks-ia1.csv');
    }

    public function test_complaint_with_attachment_and_calendar_link(): void
    {
        $this->signIn(['roles' => ['student']]);
        Http::fake([
            self::API.'files' => Http::response(['data' => ['id' => 'f1']], 201),
            self::API.'me/complaints' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'k1']], 201) : Http::response(['data' => []]),
            self::API.'complaint-categories' => Http::response(['data' => [['id' => 'cat1', 'name' => 'Infrastructure', 'sla_hours' => 48, 'is_restricted' => false, 'status' => 'active']]]),
            self::API.'me/timetable/calendar-link' => Http::response(['data' => ['url' => 'http://api.test/api/v1/calendar/x/y/timetable.ics?signature=abc']]),
            self::API.'me/timetable*' => Http::response(['data' => ['as' => 'student', 'date' => '2026-11-02', 'today' => [], 'week' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'entries' => []]]]),
        ]);

        Livewire::test(MyComplaints::class)->call('open')
            ->set('form.category_id', 'cat1')->set('form.subject', 'Leak')->set('form.description', 'Ceiling leaks')
            ->set('files', [UploadedFile::fake()->image('leak.png')])
            ->call('raise')->assertRedirect(route('complaints.show', 'k1'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'me/complaints') && $r->method() === 'POST' && $r['attachment_ids'] === ['f1']);

        Livewire::test(MyTimetable::class)->call('calendarLink')->assertSee('timetable.ics?signature=abc');
    }
}
