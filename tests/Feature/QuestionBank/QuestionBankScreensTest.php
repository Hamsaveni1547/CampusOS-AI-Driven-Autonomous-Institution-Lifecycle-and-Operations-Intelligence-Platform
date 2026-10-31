<?php

namespace Tests\Feature\QuestionBank;

use App\Livewire\QuestionBank\Papers;
use App\Livewire\QuestionBank\PaperShow;
use App\Livewire\QuestionBank\Questions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionBankScreensTest extends TestCase
{
    private const META = ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['qbank.questions.contribute', 'qbank.papers.set', 'qbank.papers.review', 'qbank.logs.view'], 'roles' => ['hod']]);
        $labels = fn (array $values) => array_map(fn (string $v) => ['value' => $v, 'label' => ucfirst($v)], $values);
        Http::fake([
            self::API.'qbank/options' => Http::response(['data' => ['types' => $labels(['mcq', 'short', 'long']), 'difficulties' => $labels(['easy', 'medium', 'hard']), 'bloom_levels' => $labels(['remember', 'apply']), 'paper_statuses' => $labels(['draft', 'submitted', 'approved', 'sealed']), 'release_minutes_before' => 30]]),
            self::API.'courses?*' => Http::response(['data' => [['id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS']], 'meta' => self::META]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paper(string $status, bool $canReview = false): array
    {
        return [
            'id' => 'qp1', 'set_code' => 'A', 'status' => $status, 'remarks' => null, 'course' => ['id' => 'c1', 'code' => '21CS51', 'title' => 'DBMS'],
            'blueprint' => ['id' => 'bp1', 'name' => 'End-term', 'total_marks' => 24.0, 'duration_minutes' => 180], 'exam' => null, 'release_window' => null,
            'set_by' => 'Dr. Kavya', 'approved_by' => null, 'sealed_at' => null, 'created_at' => now()->toIso8601String(),
            'can' => ['view' => true, 'submit' => $status === 'draft', 'review' => $canReview, 'seal' => false, 'delete' => $status === 'draft', 'logs' => true],
        ];
    }

    public function test_questions_are_listed_and_an_mcq_is_added(): void
    {
        Http::fake([
            self::API.'courses/c1/questions*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'q2']], 201) : Http::response(['data' => [['id' => 'q1', 'unit' => 1, 'type' => 'short', 'marks' => 2.0, 'co_code' => 'CO1', 'bloom_level' => 'remember', 'difficulty' => 'easy', 'status' => 'active', 'text' => 'Define a key.', 'options' => null, 'answer' => null, 'can_edit' => true]], 'meta' => self::META]),
            self::API.'courses/c1/question-coverage' => Http::response(['data' => ['total' => 1, 'by_unit' => ['1' => 1], 'by_type' => ['short · 2' => 1], 'by_bloom' => ['remember' => 1]]]),
        ]);

        Livewire::withQueryParams(['course' => 'c1'])->test(Questions::class)
            ->assertSee('Define a key.')
            ->assertSee('1 active questions')
            ->call('open')
            ->set('form.type', 'mcq')
            ->set('form.marks', '1')
            ->set('form.text', 'Which is a key?')
            ->set('form.options.0', 'Primary')
            ->set('form.options.1', 'Foreign')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'courses/c1/questions') && $r['options'] === ['Primary', 'Foreign'] && $r['marks'] === 1.0);
    }

    public function test_blueprint_is_saved_and_papers_generated(): void
    {
        Http::fake([
            self::API.'courses/c1/paper-blueprints' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'bp2']], 201) : Http::response(['data' => [['id' => 'bp1', 'name' => 'End-term', 'duration_minutes' => 180, 'avoid_recent_papers' => 2, 'sections' => [['name' => 'Part A', 'type' => 'short', 'marks' => 2.0, 'count' => 10, 'attempt' => null]], 'total_marks' => 20.0, 'papers' => 0]]]),
            self::API.'courses/c1/upcoming-exams' => Http::response(['data' => [['id' => 'sc1', 'cycle' => 'Nov 2026', 'date' => '2026-11-20', 'starts_at' => '10:00']]]),
            self::API.'paper-blueprints/bp1/generate' => Http::response(['data' => [['id' => 'qp1', 'set_code' => 'A'], ['id' => 'qp2', 'set_code' => 'B']]]),
            self::API.'question-papers?*' => Http::response(['data' => [$this->paper('draft')], 'meta' => self::META]),
        ]);

        Livewire::withQueryParams(['course' => 'c1'])->test(Papers::class)
            ->assertSee('End-term')
            ->assertSee('Set A')
            ->call('openBlueprint')
            ->set('blueprint.name', 'Mid-term')
            ->call('addSection')
            ->set('blueprint.sections.1.name', 'Part B')
            ->set('blueprint.sections.1.attempt', '3')
            ->call('saveBlueprint')
            ->assertHasNoErrors()
            ->call('openGenerate', 'bp1')
            ->set('generation.sets', 2)
            ->set('generation.exam_schedule_id', 'sc1')
            ->call('generate');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'courses/c1/paper-blueprints') && count($r['sections']) === 2 && $r['sections'][1]['attempt'] === 3 && $r['sections'][0]['attempt'] === null);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'bp1/generate') && $r['sets'] === 2 && $r['exam_schedule_id'] === 'sc1');
    }

    public function test_paper_is_opened_reviewed_and_logged(): void
    {
        Http::fake([
            self::API.'question-papers/qp1' => Http::response(['data' => $this->paper('submitted', canReview: true)]),
            self::API.'question-papers/qp1/content' => Http::response(['data' => ['answers' => true, 'sections' => [['name' => 'Part A', 'marks' => 2.0, 'count' => 1, 'attempt' => null, 'questions' => [['number' => 1, 'text' => 'Define a key.', 'options' => null, 'answer' => 'A unique identifier', 'marks' => 2.0, 'co_code' => 'CO1', 'bloom_level' => 'remember']]]]]]),
            self::API.'question-papers/qp1/access-logs' => Http::response(['data' => [['action' => 'viewed', 'user' => 'Dr. Ravi', 'ip' => '127.0.0.1', 'at' => now()->toIso8601String()]]]),
            self::API.'question-papers/qp1/review' => Http::response(['data' => $this->paper('draft')]),
        ]);

        Livewire::test(PaperShow::class, ['paper' => 'qp1'])
            ->assertDontSee('Define a key.')
            ->set('showContent', true)
            ->assertSee('Define a key.')
            ->assertSee('Answer: A unique identifier')
            ->assertSee('Dr. Ravi')
            ->set('remarks', 'Too easy')
            ->call('step', 'review', false);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'qp1/review') && $r['approve'] === false && $r['remarks'] === 'Too easy');
    }

    public function test_closed_release_window_shows_a_message_instead_of_the_paper(): void
    {
        Http::fake([
            self::API.'question-papers/qp1' => Http::response(['data' => $this->paper('sealed')]),
            self::API.'question-papers/qp1/content' => Http::response(['message' => 'This paper can be opened from 20 Nov 2026 09:30 to 13:00.', 'code' => 'OUTSIDE_RELEASE_WINDOW'], 403),
            self::API.'question-papers/qp1/access-logs' => Http::response(['data' => []]),
        ]);

        Livewire::test(PaperShow::class, ['paper' => 'qp1'])
            ->set('showContent', true)
            ->assertSet('showContent', false)
            ->assertDontSee('Opening this paper has been logged');
    }
}
