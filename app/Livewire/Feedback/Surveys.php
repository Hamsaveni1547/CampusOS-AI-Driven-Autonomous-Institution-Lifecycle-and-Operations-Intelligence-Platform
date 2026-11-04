<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Feedback surveys: build, open and close them.
 */
#[Layout('layouts::app')]
#[Title('Feedback surveys')]
class Surveys extends Component
{
    use InteractsWithApi;

    /** @var array<string, mixed> */
    public array $form = [];

    public function open(?string $id = null): void
    {
        $s = $id ? (array) $this->api()->get('surveys/'.$id)->data() : [];
        $this->form = [
            'id' => $id ?? '', 'title' => $s['title'] ?? '', 'description' => $s['description'] ?? '', 'kind' => $s['kind'] ?? 'course',
            'term_id' => $s['term']['id'] ?? (array_key_first(app(Lookups::class)->terms()) ?? ''), 'audience' => $s['audience'] ?? 'students',
            'opens_on' => $s['opens_on'] ?? now()->toDateString(), 'closes_on' => $s['closes_on'] ?? now()->addWeeks(2)->toDateString(),
            'questions' => collect($s['questions'] ?? [
                ['text' => 'The teacher explains concepts clearly', 'type' => 'rating', 'options' => null, 'required' => true],
                ['text' => 'Any suggestions for this course?', 'type' => 'text', 'options' => null, 'required' => false],
            ])->map(fn (array $q) => ['text' => $q['text'], 'type' => $q['type'], 'options' => implode(', ', $q['options'] ?? []), 'required' => $q['required']])->all(),
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'survey-form');
    }

    public function addQuestion(): void
    {
        $this->form['questions'][] = ['text' => '', 'type' => 'rating', 'options' => '', 'required' => true];
    }

    public function removeQuestion(int $index): void
    {
        unset($this->form['questions'][$index]);
        $this->form['questions'] = array_values($this->form['questions']);
    }

    public function save(): void
    {
        $this->validate(['form.title' => 'required|string|max:200', 'form.opens_on' => 'required|date', 'form.closes_on' => 'required|date|after_or_equal:form.opens_on', 'form.questions' => 'required|array|min:1', 'form.questions.*.text' => 'required|string|max:500']);
        $f = $this->form;
        $data = [
            'title' => $f['title'], 'description' => $f['description'] ?: null, 'kind' => $f['kind'], 'term_id' => $f['kind'] === 'course' ? $f['term_id'] : null,
            'audience' => $f['audience'], 'opens_on' => $f['opens_on'], 'closes_on' => $f['closes_on'],
            'questions' => array_map(fn (array $q) => ['text' => $q['text'], 'type' => $q['type'], 'options' => $q['type'] === 'choice' ? array_values(array_filter(array_map('trim', explode(',', (string) $q['options'])))) : null, 'required' => (bool) $q['required']], $f['questions']),
        ];

        $this->callApi(fn () => $f['id'] ? $this->api()->patch('surveys/'.$f['id'], $data) : $this->api()->post('surveys', $data),
            fieldMap: ['title' => 'form.title', 'term_id' => 'form.term_id', 'closes_on' => 'form.closes_on'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'survey-form');
                $this->toast('success', __('Survey saved'));
            });
    }

    public function setStatus(string $id, string $action): void
    {
        $this->callApi(fn () => $this->api()->post('surveys/'.$id.'/'.$action), onSuccess: fn () => $this->toast('success', $action === 'open' ? __('Survey opened') : __('Survey closed')));
    }

    public function render(): View
    {
        return view('livewire.feedback.surveys', [
            'surveys' => (array) $this->api()->withoutCache(fn () => $this->api()->get('surveys'))->data(),
            'terms' => app(Lookups::class)->terms(),
        ]);
    }
}
