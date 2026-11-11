<?php

namespace Tests\Feature\Ai;

use App\Livewire\Ai\Usage;
use App\Livewire\Announcements\Manage;
use App\Livewire\Feedback\MyComplaints;
use App\Livewire\Feedback\SurveyResults;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AiScreensTest extends TestCase
{
    private const ON = ['configured' => true, 'writing' => true, 'complaints' => true, 'feedback' => true, 'assistant' => true, 'documents' => true];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_announcement_writer_drafts_translates_and_undoes(): void
    {
        $this->signIn(['permissions' => ['communication.announcements.publish_all'], 'roles' => ['institution_admin']]);
        Http::fake([
            self::API.'ai/status' => Http::response(['data' => self::ON]),
            self::API.'ai/announcements/draft' => Http::response(['data' => ['title' => 'Lab exams postponed', 'body' => 'Lab exams move to Monday.']]),
            self::API.'ai/announcements/rewrite' => Http::response(['data' => ['text' => 'ಲ್ಯಾಬ್ ಪರೀಕ್ಷೆಗಳು ಸೋಮವಾರಕ್ಕೆ.']]),
            self::API.'announcements/audiences' => Http::response(['data' => ['types' => ['everyone'], 'limits' => ['departments' => null, 'sections' => null], 'roles' => [], 'categories' => [['value' => 'general', 'label' => 'General']]]]),
            self::API.'announcements*' => Http::response(['data' => [], 'meta' => ['total' => 0]]),
        ]);

        Livewire::test(Manage::class)
            ->assertSee('Write with AI')
            ->set('aiIdea', 'Lab exams postponed to Monday due to power cut')
            ->call('aiDraft')
            ->assertSet('form.title', 'Lab exams postponed')
            ->assertSet('form.body', 'Lab exams move to Monday.')
            ->call('aiRewrite', 'kannada')
            ->assertSet('form.body', 'ಲ್ಯಾಬ್ ಪರೀಕ್ಷೆಗಳು ಸೋಮವಾರಕ್ಕೆ.')
            ->call('aiUndoChange')
            ->assertSet('form.body', 'Lab exams move to Monday.');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'ai/announcements/rewrite') && $r['mode'] === 'kannada');
    }

    public function test_ai_buttons_hidden_when_ai_is_off(): void
    {
        $this->signIn(['permissions' => ['communication.announcements.publish_all'], 'roles' => ['institution_admin']]);
        Http::fake([
            self::API.'ai/status' => Http::response(['data' => ['configured' => false, 'writing' => false]]),
            self::API.'announcements/audiences' => Http::response(['data' => ['types' => ['everyone'], 'limits' => ['departments' => null, 'sections' => null], 'roles' => [], 'categories' => []]]),
            self::API.'announcements*' => Http::response(['data' => [], 'meta' => ['total' => 0]]),
        ]);

        Livewire::test(Manage::class)->assertDontSee('Write with AI');
    }

    public function test_complaint_category_suggestion_and_survey_insights(): void
    {
        $this->signIn(['permissions' => ['feedback.results.view'], 'roles' => ['hod']]);
        Http::fake([
            self::API.'ai/status' => Http::response(['data' => self::ON]),
            self::API.'ai/complaints/suggest-category' => Http::response(['data' => ['category_id' => 'cat2', 'category' => 'Infrastructure', 'urgency' => 'medium', 'reason' => 'Building repair.']]),
            self::API.'complaint-categories' => Http::response(['data' => [['id' => 'cat1', 'name' => 'Academic', 'status' => 'active'], ['id' => 'cat2', 'name' => 'Infrastructure', 'status' => 'active']]]),
            self::API.'me/complaints' => Http::response(['data' => []]),
            self::API.'ai/surveys/s1/insights' => Http::response(['data' => ['comments' => 12, 'summary' => 'Students like the examples.', 'sentiment' => ['positive' => 8, 'neutral' => 2, 'negative' => 2], 'themes' => [['theme' => 'Clear examples', 'mentions' => 6, 'tone' => 'positive'], ['theme' => 'Pace too fast', 'mentions' => 3, 'tone' => 'negative']], 'suggestion' => 'Slow down in units 3 and 4.']]),
            self::API.'surveys/s1/results' => Http::response(['data' => ['id' => 's1', 'title' => 'Mid-semester feedback', 'opens_on' => '2026-11-01', 'closes_on' => '2026-11-15', 'status' => 'open', 'overall' => ['responses' => 6, 'hidden' => false, 'minimum' => 5, 'questions' => []], 'classes' => []]]),
        ]);

        Livewire::test(MyComplaints::class)->call('open')
            ->set('form.subject', 'Leaking roof')->set('form.description', 'Water leaks in lab 2 when it rains.')
            ->call('suggestCategory')
            ->assertSet('form.category_id', 'cat2')
            ->assertSee('Suggested: Infrastructure');

        Livewire::test(SurveyResults::class, ['survey' => 's1'])
            ->call('analyse')
            ->assertSee('Students like the examples.')
            ->assertSee('Pace too fast')
            ->assertSee('Positive 67%');
    }

    public function test_usage_page_shows_totals_and_days(): void
    {
        $this->signIn(['permissions' => ['ai.usage.view', 'admin.settings.manage'], 'roles' => ['institution_admin']]);
        Http::fake([
            self::API.'ai/usage*' => Http::response(['data' => [
                'provider' => 'groq', 'fallback' => 'gemini', 'days' => 7,
                'totals' => ['calls' => 42, 'failed' => 2, 'limited' => 0, 'tokens' => 51234, 'average_ms' => 1250],
                'by_day' => [['date' => now()->subDay()->toDateString(), 'calls' => 20, 'failed' => 1], ['date' => now()->toDateString(), 'calls' => 22, 'failed' => 1]],
                'by_feature' => [['feature' => 'writing', 'calls' => 30, 'failed' => 1]], 'by_provider' => [['provider' => 'groq', 'calls' => 40]],
            ]]),
            self::API.'ai/models/test' => Http::response(['data' => ['ok' => true, 'model' => 'openai/gpt-oss-20b', 'reply' => 'OK', 'ms' => 640]]),
            self::API.'ai/models*' => Http::response(['data' => ['provider' => 'groq', 'fallback' => 'gemini', 'providers' => [
                ['provider' => 'groq', 'configured' => true, 'defaults' => ['fast' => 'openai/gpt-oss-20b', 'smart' => 'openai/gpt-oss-120b'], 'chosen' => ['fast' => '', 'smart' => ''], 'ok' => true, 'models' => ['openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3.8-27b'], 'error' => null],
                ['provider' => 'gemini', 'configured' => false, 'defaults' => ['fast' => 'gemini-3.1-flash-lite', 'smart' => 'gemini-3.1-flash-lite'], 'chosen' => ['fast' => '', 'smart' => ''], 'ok' => false, 'models' => [], 'error' => 'No API key in the backend .env'],
            ]]]),
            self::API.'settings/ai' => Http::response(['data' => []]),
        ]);

        Livewire::test(Usage::class)
            ->assertSee('42')->assertSee('51,234')->assertSee('Announcement writer')->assertSee('1.3 s')
            ->assertSee('qwen/qwen3.8-27b')->assertSee('No key')
            ->call('test', 'groq')->assertSee('Working: openai/gpt-oss-20b answered')
            ->set('chosen.groq.smart', 'qwen/qwen3.8-27b')
            ->call('saveModels');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), 'settings/ai') && $r['groq_smart_model'] === 'qwen/qwen3.8-27b' && $r['groq_fast_model'] === null);
    }
}
