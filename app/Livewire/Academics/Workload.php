<?php

namespace App\Livewire\Academics;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Weekly teaching hours per teacher in a term.
 */
#[Layout('layouts::app')]
#[Title('Faculty workload')]
class Workload extends Component
{
    use InteractsWithApi;

    #[Url(as: 'term', except: '')]
    public string $term = '';

    #[Url(as: 'department', except: '')]
    public string $department = '';

    public function render(): View
    {
        $lookups = app(Lookups::class);

        return view('livewire.academics.workload', [
            'report' => (array) $this->api()->get('faculty-workload', ['filter' => array_filter(['term' => $this->term, 'department' => $this->department])])->data(),
            'terms' => $lookups->terms(),
            'departments' => $lookups->departments(false),
            'components' => $lookups->academicsOptions('components'),
        ]);
    }
}
