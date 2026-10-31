<?php

namespace App\Livewire\QuestionBank;

use App\Api\Page;
use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * A course's question bank: add, edit and retire questions, with coverage counts.
 */
#[Layout('layouts::app')]
#[Title('Question bank')]
class Questions extends Component
{
    use InteractsWithApi, WithTable;

    #[Url(except: '')]
    public string $course = '';

    /** @var array<string, mixed> */
    public array $form = [];

    protected function defaultFilters(): array
    {
        return ['type' => '', 'unit' => '', 'difficulty' => '', 'status' => 'active'];
    }

    public function open(): void
    {
        $this->fillForm(null, null);
    }

    public function edit(string $id): void
    {
        $this->fillForm($id, collect($this->rows()->items)->firstWhere('id', $id));
    }

    /**
     * @param  array<string, mixed>|null  $existing
     */
    private function fillForm(?string $id, ?array $existing): void
    {
        $this->form = [
            'id' => $id, 'unit' => $existing['unit'] ?? '', 'type' => $existing['type'] ?? 'short', 'marks' => $existing['marks'] ?? '',
            'co_code' => $existing['co_code'] ?? '', 'bloom_level' => $existing['bloom_level'] ?? '', 'difficulty' => $existing['difficulty'] ?? 'medium',
            'text' => $existing['text'] ?? '', 'options' => array_pad($existing['options'] ?? [], 4, ''), 'answer' => $existing['answer'] ?? '',
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'question-form');
    }

    public function save(): void
    {
        $this->validate(['form.type' => 'required|string', 'form.marks' => 'required|numeric|min:0.5', 'form.difficulty' => 'required|string', 'form.text' => 'required|string|max:10000', 'form.unit' => 'nullable|integer|min:1']);
        $data = [
            'unit' => $this->form['unit'] !== '' ? (int) $this->form['unit'] : null, 'type' => $this->form['type'], 'marks' => (float) $this->form['marks'],
            'co_code' => $this->form['co_code'] ?: null, 'bloom_level' => $this->form['bloom_level'] ?: null, 'difficulty' => $this->form['difficulty'],
            'text' => $this->form['text'], 'options' => $this->form['type'] === 'mcq' ? array_values(array_filter($this->form['options'], 'filled')) : null, 'answer' => $this->form['answer'] ?: null,
        ];

        $this->callApi(fn () => $this->form['id'] ? $this->api()->patch('questions/'.$this->form['id'], $data) : $this->api()->post('courses/'.$this->course.'/questions', $data),
            fieldMap: ['text' => 'form.text', 'marks' => 'form.marks', 'type' => 'form.type', 'unit' => 'form.unit', 'options' => 'form.options'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'question-form');
                $this->toast('success', __('Question saved'));
            });
    }

    public function retire(string $id, bool $retired): void
    {
        $this->callApi(fn () => $this->api()->post('questions/'.$id.'/retire', ['retired' => $retired]), onSuccess: fn () => $this->toast('success', $retired ? __('Question retired') : __('Question restored')));
    }

    private function rows(): Page
    {
        return $this->api()->endpoint('courses/'.$this->course.'/questions')->page($this->activeFilters(null), $this->tableQuery());
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('qbank/options', memoize: true)->data();
        $map = fn (string $list) => collect($options[$list])->mapWithKeys(fn (array $o) => [$o['value'] => $o['label']])->all();

        return view('livewire.question-bank.questions', [
            'courses' => app(Lookups::class)->courses(),
            'rows' => $this->course !== '' ? $this->rows() : null,
            'coverage' => $this->course !== '' ? (array) $this->api()->get('courses/'.$this->course.'/question-coverage')->data() : null,
            'types' => $map('types'),
            'difficulties' => $map('difficulties'),
            'blooms' => $map('bloom_levels'),
        ]);
    }
}
