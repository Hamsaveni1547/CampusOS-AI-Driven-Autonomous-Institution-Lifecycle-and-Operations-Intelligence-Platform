<?php

namespace Tests\Feature\Timetable;

use App\Livewire\Timetable\Builder;
use App\Livewire\Timetable\MyTimetable;
use App\Livewire\Timetable\Periods;
use App\Livewire\Timetable\Viewer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class TimetableScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['timetable.view', 'timetable.manage', 'timetable.publish', 'timetable.adjust'], 'roles' => ['hod']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function timetable(string $status = 'draft', array $entries = []): array
    {
        return [
            'id' => 'tt1', 'term' => ['id' => 't1', 'name' => 'Odd 2026'], 'section' => ['id' => 's1', 'name' => 'A', 'batch' => 'BE-CSE 2026'], 'template' => ['id' => 'st1', 'name' => 'Regular day'],
            'status' => $status, 'version' => 1, 'periods_count' => count($entries), 'published_by' => null, 'published_at' => null,
            'slots' => [['id' => 'p1', 'position' => 1, 'label' => 'P1', 'starts_at' => '09:00', 'ends_at' => '10:00', 'is_break' => false], ['id' => 'br', 'position' => 2, 'label' => 'Break', 'starts_at' => '10:00', 'ends_at' => '10:15', 'is_break' => true]],
            'working_days' => [1, 2],
            'entries' => $entries,
            'classes' => [['id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'teachers' => [['id' => 'e1', 'name' => 'Dr. Kavya', 'component' => 'theory']]]],
            'planned_hours' => [['class_id' => 'os1', 'course' => '21CS51 · DBMS', 'planned' => count($entries), 'allocated' => 4]],
            'clashes' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(): array
    {
        return ['id' => 'en1', 'day_of_week' => 1, 'slot_id' => 'p1', 'class_id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A', 'component' => 'theory', 'teacher' => ['id' => 'e1', 'name' => 'Dr. Kavya'], 'room' => ['id' => 'r1', 'code' => 'B-101'], 'slot' => 'P1', 'starts_at' => '09:00', 'ends_at' => '10:00'];
    }

    public function test_period_template_editor_adds_slots_in_sequence(): void
    {
        Http::fake([self::API.'slot-templates' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => []], 201) : Http::response(['data' => []])]);

        Livewire::test(Periods::class)
            ->call('edit')
            ->set('form.name', 'Regular day')
            ->call('addSlot', true)
            ->assertSet('form.slots.1', ['label' => 'Break', 'starts_at' => '10:00', 'ends_at' => '10:15', 'is_break' => true])
            ->call('addSlot', false)
            ->assertSet('form.slots.2.label', 'P2')
            ->assertSet('form.slots.2.starts_at', '10:15')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['working_days'] === [1, 2, 3, 4, 5, 6] && count($r['slots']) === 3 && $r['slots'][1]['is_break'] === true);
    }

    public function test_builder_places_a_class_and_shows_clash_errors(): void
    {
        $saved = false;
        Http::fake([
            self::API.'rooms*' => Http::response(['data' => [['id' => 'r1', 'code' => 'B-101', 'name' => null, 'capacity' => 60]], 'meta' => ['total' => 1]]),
            self::API.'timetables/tt1/entries' => Http::sequence()
                ->push($this->apiError('The teacher is already booked then for BE-CSE 2026 B.', 'TEACHER_BUSY'), 409)
                ->push(['data' => []]),
            self::API.'timetables/tt1/publish' => Http::response(['data' => []]),
            self::API.'timetables/tt1' => function () use (&$saved) {
                return Http::response(['data' => $this->timetable('draft', $saved ? [$this->entry()] : [])]);
            },
        ]);

        $component = Livewire::test(Builder::class, ['timetable' => 'tt1'])
            ->assertSee('Break')
            ->assertSee('0 / 4')
            ->call('openCell', 1, 'p1')
            ->set('cell.offering_section_id', 'os1')->set('cell.room_id', 'r1')
            ->call('saveCell')
            ->assertSet('formError', 'The teacher is already booked then for BE-CSE 2026 B.');

        $saved = true;
        $component->call('saveCell')->assertHasNoErrors()->assertSee('Dr. Kavya')->assertSee('1 / 4')->call('publish');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'tt1/entries') && $r['day_of_week'] === 1 && $r['time_slot_id'] === 'p1' && $r['room_id'] === 'r1' && ! array_key_exists('employee_id', $r->data()));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'tt1/publish'));
    }

    public function test_viewer_shows_day_changes_and_assigns_a_substitute(): void
    {
        $day = [['entry_id' => 'en1', 'adjustment_id' => null, 'slot' => 'P1', 'starts_at' => '09:00', 'ends_at' => '10:00', 'class_id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A', 'teacher' => 'Dr. Kavya', 'room' => 'B-101', 'status' => 'scheduled', 'reason' => null],
            ['entry_id' => 'en2', 'adjustment_id' => 'ad1', 'slot' => 'P2', 'starts_at' => '10:15', 'ends_at' => '11:15', 'class_id' => 'os2', 'course' => ['code' => '21CS52', 'title' => 'AI'], 'section' => 'BE-CSE 2026 A', 'teacher' => 'Dr. Ravi', 'room' => null, 'status' => 'cancelled', 'reason' => 'Meeting']];
        Http::fake([
            self::API.'batches*' => Http::response(['data' => [['id' => 'b1', 'name' => 'BE-CSE 2026', 'sections' => [['id' => 's1', 'name' => 'A']]]]]),
            self::API.'employees*' => Http::response(['data' => [['id' => 'e2', 'name' => 'Dr. Ravi', 'employee_code' => 'E2', 'department' => null]], 'meta' => ['total' => 1]]),
            self::API.'timetable-view/week*' => Http::response(['data' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'entries' => [$this->entry()]]]),
            self::API.'timetable-view/day*' => Http::response(['data' => ['date' => '2026-11-02', 'periods' => $day]]),
            self::API.'timetables/tt1' => Http::response(['data' => $this->timetable('published', [$this->entry()])]),
            self::API.'timetables*' => Http::response(['data' => [$this->timetable('published')]]),
            self::API.'timetable-adjustments/ad1' => Http::response(['message' => 'ok']),
            self::API.'timetable-adjustments' => Http::response(['data' => ['id' => 'ad2', 'message' => 'Substitute assigned.']], 201),
        ]);

        Livewire::test(Viewer::class)
            ->set('target', 's1')->set('date', '2026-11-02')
            ->assertSee('Cancelled')
            ->assertSee('Meeting')
            ->assertSee(now()->startOfWeek()->translatedFormat('l'))
            ->call('openChange', 'en1', 'substituted')
            ->call('saveChange')
            ->assertHasErrors(['change.substitute_employee_id'])
            ->set('change.substitute_employee_id', 'e2')
            ->call('saveChange')
            ->assertHasNoErrors()
            ->call('undo', 'ad1');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'timetable-adjustments') && $r['entry_id'] === 'en1' && $r['type'] === 'substituted' && $r['date'] === '2026-11-02' && $r['substitute_employee_id'] === 'e2');
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), 'timetable-adjustments/ad1'));
    }

    public function test_my_timetable_for_a_student(): void
    {
        $this->signIn(['permissions' => [], 'roles' => ['student']]);
        Http::fake([self::API.'me/timetable*' => Http::response(['data' => ['as' => 'student', 'date' => '2026-11-02', 'today' => [['entry_id' => 'en1', 'adjustment_id' => null, 'slot' => 'P1', 'starts_at' => '09:00', 'ends_at' => '10:00', 'class_id' => 'os1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'BE-CSE 2026 A', 'teacher' => 'Dr. Kavya', 'room' => 'B-101', 'status' => 'scheduled', 'reason' => null]], 'week' => ['term' => ['id' => 't1', 'name' => 'Odd 2026'], 'entries' => [$this->entry()]]]])]);

        Livewire::test(MyTimetable::class)
            ->assertSee('Dr. Kavya')
            ->assertDontSee('Substitute')
            ->call('shiftDay', 1)
            ->assertSet('date', now()->addDay()->toDateString());
    }

    public function test_timetable_pages_need_permission(): void
    {
        $this->fakeShell();

        $this->signIn(['permissions' => ['identity.users.view'], 'roles' => ['student']])->get(route('timetables.index'))->assertForbidden();
    }
}
