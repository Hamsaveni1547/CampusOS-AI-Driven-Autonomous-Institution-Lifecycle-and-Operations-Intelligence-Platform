<?php

namespace App\Livewire\Academics\Curricula;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Curriculum versions per programme and regulation.
 */
#[Layout('layouts::app')]
#[Title('Curricula')]
class Index extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, string> */
    public array $form = ['programme_id' => '', 'regulation' => '', 'copy_from' => ''];

    protected function defaultFilters(): array
    {
        return ['programme' => '', 'status' => ''];
    }

    public function openCreate(?string $copyFrom = null): void
    {
        $this->form = ['programme_id' => (string) $this->filters['programme'], 'regulation' => '', 'copy_from' => (string) $copyFrom];

        if ($copyFrom !== null) {
            $source = $this->api()->endpoint('curricula')->find($copyFrom);
            $this->form['programme_id'] = $source['programme']['id'];
            $this->form['regulation'] = $source['regulation'];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'curriculum-form');
    }

    public function create(): void
    {
        $this->validate(['form.programme_id' => 'required|string', 'form.regulation' => 'required|string|max:50']);

        $response = $this->callApi(fn () => $this->api()->post('curricula', array_filter($this->form)));

        if ($response !== null) {
            $this->dispatch('close-dialog', 'curriculum-form');
            $this->redirectRoute('curricula.show', $response->data('id'), navigate: true);
        }
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $programmes = $lookups->programmes(false);
        $statuses = $lookups->academicsOptions('curriculum_statuses');

        return view('livewire.academics.curricula.index', [
            'curricula' => $this->api()->endpoint('curricula')->all($this->activeFilters(null), memoize: false),
            'programmes' => $programmes,
            'statuses' => $statuses,
            'canManage' => $this->can('academics.curriculum.manage'),
            'chips' => $this->filterChips(['programme' => ['_name' => __('Programme')] + $programmes, 'status' => ['_name' => __('Status')] + $statuses]),
        ]);
    }
}
