<?php

namespace App\Livewire\Admissions\Applications;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Admissions desk: every submitted application, oldest first.
 */
#[Layout('layouts::app')]
#[Title('Applications')]
class Index extends Component
{
    use InteractsWithApi, WithTable;

    protected function defaultFilters(): array
    {
        return ['status' => '', 'cycle' => '', 'programme' => '', 'quota' => ''];
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $statuses = $lookups->admissionOptions('statuses');
        $quotas = $lookups->admissionOptions('quotas');
        $cycles = collect($this->api()->endpoint('admission-cycles')->all())->mapWithKeys(fn (array $cycle) => [$cycle['id'] => $cycle['name']])->all();
        $programmes = $lookups->programmes(false);

        return view('livewire.admissions.applications.index', [
            'rows' => $this->api()->endpoint('applications')->page($this->activeFilters(), $this->tableQuery()),
            'summary' => $this->filters['cycle'] !== '' ? (array) $this->api()->get('applications/summary', ['filter' => ['cycle' => $this->filters['cycle']]])->data() : [],
            'statuses' => $statuses,
            'quotas' => $quotas,
            'cycles' => $cycles,
            'programmes' => $programmes,
            'chips' => $this->filterChips([
                'status' => ['_name' => __('Status')] + $statuses,
                'cycle' => ['_name' => __('Cycle')] + $cycles,
                'programme' => ['_name' => __('Programme')] + $programmes,
                'quota' => ['_name' => __('Quota')] + $quotas,
            ]),
        ]);
    }
}
