<?php

namespace Tests\Feature\Mentoring;

use App\Livewire\Mentoring\Allocation;
use App\Livewire\Mentoring\MenteeShow;
use App\Livewire\Mentoring\MyMentees;
use App\Livewire\Mentoring\MyMentor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class MentoringScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_hod_allocates_and_changes_a_mentor(): void
    {
        $this->signIn(['permissions' => ['mentoring.allocate', 'mentoring.reports.view'], 'roles' => ['hod']]);
        Http::fake([
            self::API.'batches*' => Http::response(['data' => [['id' => 'b1', 'name' => 'BE CSE 2024']]]),
            self::API.'sections*' => Http::response(['data' => [['id' => 'sec1', 'name' => 'A']]]),
            self::API.'employees*' => Http::response(['data' => [['id' => 'e1', 'name' => 'Kavya Shetty', 'employee_code' => 'BIT001', 'designation' => 'Assistant Professor', 'department' => ['name' => 'CSE']]]]),
            self::API.'mentoring/allocate' => Http::response(['data' => ['message' => '2 students allocated']]),
            self::API.'mentoring/assignments*' => Http::response(['data' => [['id' => 'st1', 'name' => 'Arjun Rao', 'roll_no' => '1', 'register_no' => '1BI24CS001', 'mentor' => null]]]),
            self::API.'students/st1/mentor' => Http::response(['data' => []]),
        ]);

        Livewire::test(Allocation::class)
            ->set('section', 'sec1')
            ->assertSee('Arjun Rao')
            ->set('mentorIds', ['e1'])
            ->call('allocate')
            ->call('openReassign', 'st1', 'Arjun Rao')
            ->set('reassign.mentor_id', 'e1')
            ->call('saveReassign')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'mentoring/allocate') && $r['section_ids'] === ['sec1'] && $r['mentor_ids'] === ['e1']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'students/st1/mentor') && $r['mentor_id'] === 'e1');
    }

    public function test_mentor_schedules_and_records_a_meeting(): void
    {
        $this->signIn(['permissions' => ['mentoring.mentor'], 'roles' => ['faculty']]);
        $meeting = ['id' => 'm1', 'agenda' => 'Attendance review', 'status' => 'scheduled', 'scheduled_at' => '2026-11-10T15:00:00+05:30', 'minutes' => null, 'students' => [['id' => 'st1', 'name' => 'Arjun Rao']]];
        Http::fake([
            self::API.'me/mentees*' => Http::response(['data' => [['id' => 'st1', 'name' => 'Arjun Rao', 'register_no' => '1BI24CS001', 'section' => 'A', 'reasons' => ['Attendance short']]]]),
            self::API.'mentoring-meetings/m1' => Http::response(['data' => []]),
            self::API.'mentoring-meetings*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'm2']], 201) : Http::response(['data' => [$meeting]]),
        ]);

        Livewire::test(MyMentees::class)
            ->assertSee('Attendance short')
            ->call('openMeeting')
            ->set('meeting.student_ids', ['st1'])
            ->set('meeting.agenda', 'Plan for exams')
            ->call('schedule')
            ->call('openRecord', $meeting, 'held')
            ->set('record.minutes', 'Agreed to attend extra classes')
            ->call('saveRecord')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['student_ids'] === ['st1']);
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['status'] === 'held' && $r['attended_ids'] === ['st1']);
    }

    public function test_mentor_adds_note_and_resolves_concern(): void
    {
        $this->signIn(['permissions' => ['mentoring.mentor'], 'roles' => ['faculty']]);
        Http::fake([
            self::API.'mentees/st1/notes' => Http::response(['data' => []], 201),
            self::API.'student-concerns/c1' => Http::response(['data' => []]),
            self::API.'mentees/st1' => Http::response(['data' => [
                'id' => 'st1', 'name' => 'Arjun Rao', 'register_no' => '1BI24CS001', 'programme' => 'BE CSE', 'section' => 'A', 'email' => 'arjun@x.test', 'phone' => null,
                'flags' => ['Fees overdue'], 'mentor' => ['name' => 'Kavya Shetty', 'since' => '2026-08-01'],
                'result' => ['cgpa' => 7.8, 'backlogs' => 0, 'cycle' => 'Jan 2026'], 'fees' => ['balance' => 25000, 'overdue' => 25000],
                'attendance' => [], 'guardians' => [['relation' => 'father', 'name' => 'Ravi Rao', 'phone' => '9800000000']],
                'notes' => [['id' => 'n1', 'body' => 'Talked about fees', 'visibility' => 'shared', 'by' => 'Kavya Shetty', 'at' => '2026-11-01T10:00:00+05:30']],
                'concerns' => [['id' => 'c1', 'category' => 'financial', 'description' => 'Fee delay', 'action_plan' => null, 'review_on' => null, 'status' => 'open', 'overdue' => false, 'outcome' => null]],
                'meetings' => [],
            ]]),
        ]);

        Livewire::test(MenteeShow::class, ['student' => 'st1'])
            ->assertSee('Talked about fees')
            ->set('note.body', 'Parents informed')
            ->call('addNote')
            ->call('openResolve', 'c1')
            ->set('resolution.outcome', 'Paid in instalments')
            ->call('resolve')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'st1/notes') && $r['body'] === 'Parents informed');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'student-concerns/c1') && $r['resolve'] === true);
    }

    public function test_student_requests_a_meeting(): void
    {
        $this->signIn(['roles' => ['student']]);
        Http::fake([
            self::API.'me/mentor/meeting-requests' => Http::response(['data' => []], 201),
            self::API.'me/mentor' => Http::response(['data' => ['mentor' => ['name' => 'Kavya Shetty', 'designation' => 'Assistant Professor', 'department' => 'CSE', 'email' => 'kavya@x.test', 'since' => '2026-08-01'], 'meetings' => []]]),
        ]);

        Livewire::test(MyMentor::class)
            ->assertSee('Kavya Shetty')
            ->set('agenda', 'Career guidance')
            ->call('request')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'meeting-requests') && $r['agenda'] === 'Career guidance');
    }
}
