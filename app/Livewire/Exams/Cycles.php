<?php

namespace App\Livewire\Exams;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Exam cycles, newest first, and creating a new one.
 */
#[Layout('layouts::app')]
#[Title('Examinations')]
class Cycles extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, string> */
    public array $form = ['name' => '', 'type' => 'regular', 'term_id' => '', 'registration_ends_on' => ''];

    public function create(): void
    {
        $this->form = ['name' => '', 'type' => 'regular', 'term_id' => array_key_first(app(Lookups::class)->terms()) ?? '', 'registration_ends_on' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'cycle-form');
    }

    public function save(): void
    {
        $this->validate(['form.name' => 'required|string|max:150', 'form.type' => 'required|string', 'form.term_id' => 'required|string', 'form.registration_ends_on' => 'nullable|date']);

        $this->callApi(fn () => $this->api()->post('exam-cycles', [...$this->form, 'registration_ends_on' => $this->form['registration_ends_on'] ?: null]),
            fieldMap: ['name' => 'form.name', 'type' => 'form.type', 'term_id' => 'form.term_id', 'registration_ends_on' => 'form.registration_ends_on'],
            onSuccess: fn ($response) => $this->redirectRoute('exam-cycles.show', $response->data('id'), navigate: true));
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('exams/options', memoize: true)->data();

        return view('livewire.exams.cycles', [
            'rows' => $this->api()->endpoint('exam-cycles')->page([], $this->tableQuery()),
            'types' => collect($options['types'])->mapWithKeys(fn (array $t) => [$t['value'] => $t['label']])->all(),
            'statuses' => collect($options['statuses'])->mapWithKeys(fn (array $t) => [$t['value'] => $t['label']])->all(),
            'terms' => app(Lookups::class)->terms(),
        ]);
    }
}
