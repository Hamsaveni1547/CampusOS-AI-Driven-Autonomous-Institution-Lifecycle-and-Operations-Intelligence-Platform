<?php

namespace Tests\Feature\Attendance;

use App\Livewire\Attendance\ClassAttendance;
use App\Livewire\Attendance\Corrections;
use App\Livewire\Attendance\MyAttendance;
use App\Livewire\Attendance\Shortage;
use App\Livewire\Attendance\Take;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['attendance.records.mark', 'attendance.reports.view', 'attendance.condonation.manage', 'attendance.corrections.approve'], 'roles' => ['hod']]);
        Http::fake([self::API.'attendance/options' => Http::response(['data' => [
            'statuses' => [['value' => 'present', 'label' => 'Present'], ['value' => 'absent', 'label' => 'Absent'], ['value' => 'late', 'label' => 'Late'], ['value' => 'on_duty', 'label' => 'On duty'], ['value' => 'medical', 'label' => 'Medical']],
            'counts_as_attended' => ['present', 'late', 'on_duty'], 'threshold' => 75.0, 'condonation_min' => 65.0, 'edit_window_hours' => 48,
        ]])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $name, float $percentage, string $standing): array
    {
        return ['student_id' => 'st-'.$name, 'register_no' => 'R-'.$name, 'roll_no' => '01', 'name' => $name, 'held' => 7, 'attended' => (int) round($percentage * 7 / 100), 'percentage' => $percentage, 'condoned' => false, 'standing' => $standing];
    }

    public function test_teacher_marks_a_pending_class_with_everyone_present_by_default(): void
    {
        $date = now()->subDay()->toDateString();
        Http::fake([
            self::API.'me/attendance/pending' => Http::response(['data' => [['entry_id' => 'en1', 'adjustment_id' => null, 'slot_id' => 'p1', 'slot' => 'P1', 'starts_at' => '09:00', 'ends_at' => '10:00', 'class_id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A', 'teacher' => 'Dr. Kavya', 'room' => null, 'status' => 'scheduled', 'reason' => null, 'date' => $date]]]),
            self::API.'attendance/sheet*' => fn (Request $r) => $r->method() === 'POST'
                ? Http::response(['data' => ['id' => 'as1', 'message' => 'Attendance saved. 1 absent.']])
                : Http::response(['data' => [
                    'class' => ['id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A'], 'date' => $date, 'slot' => ['id' => 'p1', 'label' => 'P1', 'starts_at' => '09:00'],
                    'session' => null, 'can_mark' => true,
                    'students' => [['student_id' => 'st1', 'register_no' => 'R1', 'roll_no' => '01', 'name' => 'Asha', 'status' => null], ['student_id' => 'st2', 'register_no' => 'R2', 'roll_no' => '02', 'name' => 'Bala', 'status' => null]],
                ]]),
        ]);

        Livewire::test(Take::class)
            ->assertSee('DBMS')
            ->call('open', 'os1', $date, 'p1')
            ->assertSet('statuses', ['st1' => 'present', 'st2' => 'present'])
            ->assertSee('Present: 2')
            ->set('statuses.st2', 'absent')
            ->assertSee('Absent: 1')
            ->set('notes', 'Normalisation')
            ->call('save')
            ->assertSet('class', '');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['class_id'] === 'os1' && $r['time_slot_id'] === 'p1' && $r['statuses'] === ['st2' => 'absent'] && $r['notes'] === 'Normalisation');
    }

    public function test_closed_sheet_is_read_only(): void
    {
        Http::fake([self::API.'attendance/sheet*' => Http::response(['data' => [
            'class' => ['id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A'], 'date' => '2026-10-20', 'slot' => null,
            'session' => ['id' => 'as1', 'marked_by' => 'Dr. Kavya', 'marked_at' => now()->subDays(5)->toIso8601String(), 'notes' => null, 'open' => false], 'can_mark' => false,
            'students' => [['student_id' => 'st1', 'register_no' => 'R1', 'roll_no' => '01', 'name' => 'Asha', 'status' => 'absent']],
        ]])]);

        Livewire::withQueryParams(['class' => 'os1', 'date' => '2026-10-20'])->test(Take::class)
            ->assertSee('The time to change it has passed')
            ->assertDontSee('Save attendance')
            ->assertSet('statuses', ['st1' => 'absent']);
    }

    public function test_class_summary_and_shortage_with_condonation(): void
    {
        Http::fake([
            self::API.'attendance/classes/os1/summary' => Http::response(['data' => ['class' => ['id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A'], 'students' => [$this->row('Asha', 100.0, 'ok'), $this->row('Bala', 71.43, 'condonable')]]]),
            self::API.'batches*' => Http::response(['data' => [['id' => 'b1', 'name' => 'BE-CSE 2026', 'sections' => [['id' => 's1', 'name' => 'A']]]]]),
            self::API.'terms*' => Http::response(['data' => []]),
            self::API.'attendance/shortage*' => Http::response(['data' => ['section' => 'BE-CSE 2026 A', 'rows' => [$this->row('Bala', 71.43, 'condonable') + ['class_id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS']]]]]),
            self::API.'attendance/condonations' => Http::response(['data' => ['id' => 'c1', 'message' => 'Shortage condoned.']], 201),
        ]);

        Livewire::test(ClassAttendance::class, ['class' => 'os1'])->assertSee('Asha')->assertSee('Can be condoned');

        Livewire::test(Shortage::class)
            ->assertSee('Choose a section')
            ->set('section', 's1')
            ->assertSee('71.43%')
            ->call('openCondone', 'st-Bala', 'os1', 'Bala · 21CS51')
            ->call('saveCondone')
            ->assertHasErrors(['condone.reason'])
            ->set('condone.reason', 'Hospitalised')->set('condone.fee_reference', 'CND-1')
            ->call('saveCondone')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'attendance/condonations') && $r['student_id'] === 'st-Bala' && $r['class_id'] === 'os1' && $r['fee_reference'] === 'CND-1');
    }

    public function test_corrections_queue(): void
    {
        Http::fake([
            self::API.'attendance-corrections/ac1/decide' => Http::response(['data' => ['id' => 'ac1', 'status' => 'approved']]),
            self::API.'attendance-corrections*' => Http::response(['data' => [['id' => 'ac1', 'student' => ['id' => 'st2', 'name' => 'Bala', 'register_no' => 'R2'], 'course' => '21CS51', 'section' => 'BE-CSE 2026 A', 'date' => '2026-10-20', 'slot' => 'P1', 'current_status' => 'absent', 'requested_status' => 'on_duty', 'reason' => 'Hackathon', 'requested_by' => 'Bala', 'status' => 'pending', 'remarks' => null, 'reviewed_by' => null, 'created_at' => now()->toIso8601String()]], 'meta' => ['total' => 1]]),
        ]);

        Livewire::test(Corrections::class)->assertSee('Hackathon')->assertSee('On duty')->call('approve', 'ac1');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'ac1/decide') && $r['approve'] === true);
    }

    public function test_student_sees_percentages_and_asks_for_a_correction(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['student']]);
        Http::fake([
            self::API.'me/attendance/os1' => Http::response(['data' => ['class' => ['id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS']], 'records' => [['session_id' => 'as1', 'date' => '2026-10-20', 'slot' => 'P1', 'status' => 'absent', 'correction' => null]]]]),
            self::API.'me/attendance' => Http::response(['data' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'courses' => [['class_id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'held' => 7, 'attended' => 5, 'percentage' => 71.43, 'condoned' => false, 'standing' => 'condonable']]]]),
            self::API.'attendance-corrections' => Http::response(['data' => ['id' => 'ac1', 'message' => 'Correction requested.']], 201),
        ]);

        Livewire::test(MyAttendance::class)
            ->assertSee('5 of 7 classes')
            ->set('class', 'os1')
            ->assertSee('Ask for correction')
            ->call('openCorrection', 'as1')
            ->set('correction.reason', 'Represented college')
            ->call('requestCorrection')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'attendance-corrections') && $r['session_id'] === 'as1' && $r['requested_status'] === 'on_duty');
    }

    public function test_attendance_pages_need_permission(): void
    {
        $this->fakeShell();

        $this->signIn(['permissions' => ['identity.users.view'], 'roles' => ['student']])->get(route('attendance.take'))->assertForbidden();
    }
}
