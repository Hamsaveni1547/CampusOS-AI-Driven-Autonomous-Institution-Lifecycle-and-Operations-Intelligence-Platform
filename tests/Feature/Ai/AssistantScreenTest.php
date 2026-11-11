<?php

namespace Tests\Feature\Ai;

use App\Livewire\Ai\Assistant;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AssistantScreenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_student_asks_a_suggested_question_and_continues_the_chat(): void
    {
        $this->signIn(['permissions' => ['ai.assistant.use'], 'roles' => ['student']]);
        Http::fake([
            self::API.'ai/assistant/messages' => Http::sequence()
                ->push(['data' => ['conversation_id' => 'cv1', 'title' => 'Attendance', 'message' => ['role' => 'assistant', 'content' => 'You are short in DBMS (68%).', 'tools' => ['my_attendance'], 'at' => now()->toIso8601String()]]])
                ->push(['data' => ['conversation_id' => 'cv1', 'title' => 'Attendance', 'message' => ['role' => 'assistant', 'content' => 'Attend 6 more DBMS classes.', 'tools' => [], 'at' => now()->toIso8601String()]]]),
            self::API.'ai/assistant/conversations/cv1' => Http::response(['data' => ['id' => 'cv1', 'title' => 'Attendance', 'messages' => [['role' => 'user', 'content' => 'Am I short?', 'tools' => [], 'at' => now()->toIso8601String()]]]]),
            self::API.'ai/assistant' => Http::response(['data' => ['conversations' => [['id' => 'cv1', 'title' => 'Attendance', 'updated_at' => now()->toIso8601String()]], 'tools' => ['my_attendance', 'my_timetable', 'my_fees', 'my_results', 'my_library_loans', 'my_dashboard', 'latest_announcements']]]),
        ]);

        Livewire::test(Assistant::class)
            ->assertSee('Am I short of attendance in any course?')
            ->call('ask', 'Am I short of attendance in any course?')
            ->assertSet('conversationId', 'cv1')
            ->assertSee('You are short in DBMS (68%).')
            ->assertSee('attendance')
            ->set('question', 'How many classes should I attend?')
            ->call('ask')
            ->assertSee('Attend 6 more DBMS classes.')
            ->call('open', 'cv1')
            ->assertSet('messages.0.content', 'Am I short?');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'ai/assistant/messages') && ($r->data()['conversation_id'] ?? null) === 'cv1' && $r['message'] === 'How many classes should I attend?');
    }
}
