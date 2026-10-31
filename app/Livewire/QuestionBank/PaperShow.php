<?php

namespace App\Livewire\QuestionBank;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One question paper: review actions, opening its content (logged) and the access log.
 */
#[Layout('layouts::app')]
class PaperShow extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $paperId = '';

    public bool $showContent = false;

    public string $remarks = '';

    public function mount(string $paper): void
    {
        $this->paperId = $paper;
    }

    public function step(string $action, ?bool $approve = null): void
    {
        $data = $action === 'review' ? ['approve' => (bool) $approve, 'remarks' => $this->remarks ?: null] : [];
        $messages = ['submit' => __('Sent for review'), 'review' => $approve ? __('Paper approved') : __('Sent back to the setter'), 'seal' => __('Paper sealed')];

        $this->callApi(fn () => $this->api()->post('question-papers/'.$this->paperId.'/'.$action, $data), fieldMap: ['remarks' => 'remarks'], onSuccess: function () use ($action, $messages): void {
            $this->remarks = '';
            $this->showContent = false;
            $this->dispatch('close-dialog', 'return-paper');
            $this->toast('success', $messages[$action]);
        });
    }

    public function delete(): void
    {
        $this->callApi(fn () => $this->api()->delete('question-papers/'.$this->paperId), onSuccess: fn () => $this->redirectRoute('question-papers.index', navigate: true));
    }

    public function render(): View
    {
        $paper = (array) $this->api()->withoutCache(fn () => $this->api()->get('question-papers/'.$this->paperId))->data();
        $content = null;

        if ($this->showContent) {
            $this->callApi(function () use (&$content) {
                $response = $this->api()->withoutCache(fn () => $this->api()->get('question-papers/'.$this->paperId.'/content'));
                $content = (array) $response->data();

                return $response;
            });

            if ($content === null) {
                $this->showContent = false;
            }
        }

        return view('livewire.question-bank.paper-show', [
            'paper' => $paper,
            'content' => $content,
            'logs' => $paper['can']['logs'] ? (array) $this->api()->withoutCache(fn () => $this->api()->get('question-papers/'.$this->paperId.'/access-logs'))->data() : [],
        ])->title($paper['course']['code'].' '.__('Set :s', ['s' => $paper['set_code']]));
    }
}
