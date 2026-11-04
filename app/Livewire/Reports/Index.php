<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The reports the user may run, grouped by module.
 */
#[Layout('layouts::app')]
#[Title('Reports')]
class Index extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        return view('livewire.reports.index', [
            'groups' => collect((array) $this->api()->get('reports', memoize: true)->data())->groupBy('module')->all(),
        ]);
    }
}
