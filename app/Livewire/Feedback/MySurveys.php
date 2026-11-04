<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Surveys waiting for the signed-in user (one per class for course feedback).
 */
#[Layout('layouts::app')]
#[Title('Give feedback')]
class MySurveys extends Component
{
    use InteractsWithApi;

    public ?int $current = null;

    /** @var array<string, mixed> */
    public array $answers = [];

    public function start(int $index): void
    {
        $this->current = $index;
        $this->answers = [];
        $this->resetErrorBag();
    }

    public function submit(): void
    {
        $item = $this->pending()[$this->current] ?? null;

        if ($item === null) {
            return;
        }

        $this->callApi(fn () => $this->api()->post('me/surveys/'.$item['id'].'/answers', ['class_id' => $item['class']['id'] ?? null, 'answers' => $this->answers]), onSuccess: function ($response): void {
            $this->current = null;
            $this->answers = [];
            $this->toast('success', (string) $response->message());
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pending(): array
    {
        return (array) $this->api()->withoutCache(fn () => $this->api()->get('me/surveys'))->data();
    }

    public function render(): View
    {
        $pending = $this->pending();

        return view('livewire.feedback.my-surveys', ['pending' => $pending, 'active' => $this->current !== null ? ($pending[$this->current] ?? null) : null]);
    }
}
