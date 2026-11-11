<?php

namespace App\Livewire\Ai;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Ask CampusOS: chat with the assistant about your own attendance, timetable, fees, results and work.
 */
#[Layout('layouts::app')]
#[Title('Ask CampusOS')]
class Assistant extends Component
{
    use InteractsWithApi;

    #[Url(as: 'c', except: '')]
    public string $conversationId = '';

    /** @var list<array{role: string, content: string, tools: list<string>, at: string}> */
    public array $messages = [];

    public string $question = '';

    public function mount(): void
    {
        if ($this->conversationId !== '') {
            $this->open($this->conversationId);
        }
    }

    public function open(string $id): void
    {
        $this->callApi(fn () => $this->api()->get('ai/assistant/conversations/'.$id), onSuccess: function ($response) use ($id): void {
            $this->conversationId = $id;
            $this->messages = array_values((array) $response->data('messages'));
        });
    }

    public function newChat(): void
    {
        $this->conversationId = '';
        $this->messages = [];
        $this->question = '';
    }

    public function ask(?string $suggested = null): void
    {
        $question = trim($suggested ?? $this->question);

        if ($question === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $question, 'tools' => [], 'at' => now()->toIso8601String()];
        $this->question = '';

        $this->callApi(fn () => $this->api()->post('ai/assistant/messages', array_filter(['message' => $question, 'conversation_id' => $this->conversationId ?: null])), onSuccess: function ($response): void {
            $this->conversationId = (string) $response->data('conversation_id');
            $this->messages[] = (array) $response->data('message');
        });
    }

    public function delete(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('ai/assistant/conversations/'.$id), onSuccess: function () use ($id): void {
            if ($id === $this->conversationId) {
                $this->newChat();
            }
        });
    }

    public function render(): View
    {
        $data = (array) $this->api()->withoutCache(fn () => $this->api()->get('ai/assistant'))->data();
        $tools = (array) ($data['tools'] ?? []);

        $suggestions = array_values(array_filter([
            in_array('my_attendance', $tools, true) ? __('Am I short of attendance in any course?') : null,
            in_array('my_timetable', $tools, true) ? __('What classes do I have tomorrow?') : null,
            in_array('my_fees', $tools, true) ? __('How much fee is pending and when is it due?') : null,
            in_array('my_results', $tools, true) ? __('What was my SGPA last semester?') : null,
            in_array('classes_to_mark', $tools, true) ? __('Which classes have I not marked attendance for?') : null,
            in_array('my_dashboard', $tools, true) && ! in_array('my_fees', $tools, true) ? __('Give me today’s key numbers.') : null,
            in_array('latest_announcements', $tools, true) ? __('Any new announcements for me?') : null,
            'ನನ್ನ ಲೈಬ್ರರಿ ಪುಸ್ತಕಗಳು ಯಾವಾಗ ಹಿಂತಿರುಗಿಸಬೇಕು?',
        ]));

        return view('livewire.ai.assistant', [
            'conversations' => (array) ($data['conversations'] ?? []),
            'suggestions' => array_slice($suggestions, 0, 6),
            'toolLabels' => [
                'my_attendance' => __('attendance'), 'my_timetable' => __('timetable'), 'my_fees' => __('fees'), 'my_results' => __('results'),
                'my_library_loans' => __('library'), 'classes_to_mark' => __('classes to mark'), 'my_dashboard' => __('dashboard'), 'latest_announcements' => __('announcements'),
            ],
        ]);
    }
}
