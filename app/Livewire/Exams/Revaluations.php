<?php

namespace App\Livewire\Exams;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Revaluation requests: give revised marks or reject.
 */
#[Layout('layouts::app')]
#[Title('Revaluation')]
class Revaluations extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $decision = ['id' => '', 'student' => '', 'marks' => null, 'new_marks' => '', 'remarks' => ''];

    protected function defaultFilters(): array
    {
        return ['status' => 'pending'];
    }

    public function open(string $id, string $student, ?float $marks): void
    {
        $this->decision = ['id' => $id, 'student' => $student, 'marks' => $marks, 'new_marks' => '', 'remarks' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'revaluation');
    }

    public function decide(bool $reject = false): void
    {
        $this->validate($reject ? ['decision.remarks' => 'required|string|max:500'] : ['decision.new_marks' => 'required|numeric|min:0', 'decision.remarks' => 'nullable|string|max:500']);
        $data = $reject ? ['remarks' => $this->decision['remarks']] : ['new_marks' => (float) $this->decision['new_marks'], 'remarks' => $this->decision['remarks'] ?: null];

        $this->callApi(fn () => $this->api()->post('revaluation-requests/'.$this->decision['id'].'/decide', $data), fieldMap: ['new_marks' => 'decision.new_marks', 'remarks' => 'decision.remarks'], onSuccess: function () use ($reject): void {
            $this->dispatch('close-dialog', 'revaluation');
            $this->toast('success', $reject ? __('Request rejected') : __('Marks revised and result recalculated'));
        });
    }

    public function render(): View
    {
        return view('livewire.exams.revaluations', ['rows' => $this->api()->endpoint('revaluation-requests')->page($this->activeFilters(null), $this->tableQuery())]);
    }
}
