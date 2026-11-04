<?php

namespace Tests\Feature\Feedback;

use App\Livewire\Feedback\ComplaintShow;
use App\Livewire\Feedback\MyComplaints;
use App\Livewire\Feedback\MySurveys;
use App\Livewire\Feedback\Surveys;
use App\Livewire\Feedback\TeachingFeedback;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['feedback.surveys.manage', 'feedback.results.view', 'feedback.complaints.handle'], 'roles' => ['hod', 'faculty']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function results(bool $hidden): array
    {
        return ['responses' => $hidden ? 1 : 6, 'hidden' => $hidden, 'minimum' => 5, 'questions' => $hidden ? [] : [
            ['id' => 'q1', 'text' => 'Explains clearly', 'type' => 'rating', 'answers' => 6, 'average' => 4.33, 'distribution' => [1 => 0, 2 => 0, 3 => 1, 4 => 2, 5 => 3], 'choices' => null, 'comments' => null],
            ['id' => 'q2', 'text' => 'Suggestions', 'type' => 'text', 'answers' => 1, 'average' => null, 'distribution' => null, 'choices' => null, 'comments' => ['More examples please']],
        ]];
    }

    public function test_survey_is_built_and_opened(): void
    {
        Http::fake([
            self::API.'surveys' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 's1']], 201) : Http::response(['data' => [['id' => 's1', 'title' => 'Mid-semester feedback', 'description' => null, 'kind' => 'course', 'audience' => 'students', 'term' => ['id' => 't1', 'name' => 'Odd 2026'], 'opens_on' => '2026-11-01', 'closes_on' => '2026-11-15', 'status' => 'draft', 'responses' => 0]]]),
            self::API.'surveys/s1/open' => Http::response(['data' => []]),
            self::API.'terms*' => Http::response(['data' => [['id' => 't1', 'name' => 'Odd 2026', 'is_current' => true, 'starts_on' => '2026-08-01']]]),
        ]);

        Livewire::test(Surveys::class)
            ->assertSee('Mid-semester feedback')
            ->call('open')
            ->set('form.title', 'End-semester feedback')
            ->call('addQuestion')
            ->set('form.questions.2.text', 'Pace')
            ->set('form.questions.2.type', 'choice')
            ->set('form.questions.2.options', 'Too slow, Right, Too fast')
            ->call('save')
            ->assertHasNoErrors()
            ->call('setStatus', 's1', 'open');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'surveys') && $r['term_id'] === 't1' && $r['questions'][2]['options'] === ['Too slow', 'Right', 'Too fast'] && $r['questions'][1]['required'] === false);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 's1/open'));
    }

    public function test_student_answers_and_teacher_sees_results(): void
    {
        Http::fake([
            self::API.'me/surveys' => Http::response(['data' => [['id' => 's1', 'title' => 'Mid-semester feedback', 'description' => null, 'kind' => 'course', 'audience' => 'students', 'term' => null, 'opens_on' => '2026-11-01', 'closes_on' => '2026-11-15', 'status' => 'open',
                'class' => ['id' => 'c1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'A', 'teachers' => ['Dr. Kavya']],
                'questions' => [['id' => 'q1', 'text' => 'Explains clearly', 'type' => 'rating', 'options' => null, 'required' => true]]]]]),
            self::API.'me/surveys/s1/answers' => Http::response(['message' => 'Thank you. Your answers are anonymous.']),
            self::API.'me/teaching-feedback' => Http::response(['data' => [['id' => 's1', 'title' => 'Mid-semester feedback', 'term' => ['name' => 'Odd 2026'], 'classes' => [
                ['id' => 'c1', 'course' => ['code' => '21CS51', 'title' => 'DBMS'], 'section' => 'A', 'teachers' => [], 'results' => $this->results(false)],
                ['id' => 'c2', 'course' => ['code' => '21CS52', 'title' => 'OS'], 'section' => 'B', 'teachers' => [], 'results' => $this->results(true)],
            ]]]]),
        ]);

        Livewire::test(MySurveys::class)->assertSee('Dr. Kavya')->call('start', 0)->assertSee('Explains clearly')->set('answers.q1', '4')->call('submit')->assertSet('current', null);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 's1/answers') && $r['class_id'] === 'c1' && $r['answers'] === ['q1' => '4']);

        Livewire::test(TeachingFeedback::class)->assertSee('4.33')->assertSee('More examples please')->assertSee('Results appear once 5 people have answered (now 1)');
    }

    public function test_complaint_is_raised_and_handled(): void
    {
        $complaint = fn (bool $staff) => ['id' => 'k1', 'category' => ['id' => 'cat1', 'name' => 'Infrastructure', 'is_restricted' => false], 'subject' => 'Fans not working', 'description' => 'Two fans', 'is_anonymous' => true, 'raised_by' => null, 'assigned_to' => null, 'status' => 'open', 'overdue' => false, 'due_at' => now()->addDay()->toIso8601String(), 'raised_at' => now()->toIso8601String(), 'resolved_at' => null, 'rating' => null, 'can_handle' => $staff, 'messages' => []];
        Http::fake([
            self::API.'me/complaints' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'k1']], 201) : Http::response(['data' => []]),
            self::API.'complaint-categories' => Http::response(['data' => [['id' => 'cat1', 'name' => 'Infrastructure', 'sla_hours' => 48, 'is_restricted' => false, 'status' => 'active']]]),
            self::API.'complaints/k1' => Http::response(['data' => $complaint(true)]),
            self::API.'complaints/k1/respond' => Http::response(['message' => 'ok']),
        ]);

        Livewire::test(MyComplaints::class)->call('open')->set('form.category_id', 'cat1')->set('form.subject', 'Fans not working')->set('form.description', 'Two fans')->set('form.anonymous', true)->call('raise')->assertRedirect(route('complaints.show', 'k1'));
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'me/complaints') && $r['anonymous'] === true);

        Livewire::test(ComplaintShow::class, ['complaint' => 'k1'])->assertSee('Raised anonymously')->set('body', 'Fans repaired')->call('send', true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'k1/respond') && $r['resolve'] === true && $r['internal'] === false);
    }
}
