<?php

namespace App\Livewire\Assessment;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The student's assessment marks and internal marks per course this term.
 */
#[Layout('layouts::app')]
#[Title('My marks')]
class MyMarks extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        $options = (array) $this->api()->get('assessment/options', memoize: true)->data();

        return view('livewire.assessment.my-marks', [
            'data' => (array) $this->api()->get('me/internal-marks')->data(),
            'markStatuses' => collect($options['mark_statuses'])->mapWithKeys(fn (array $s) => [$s['value'] => $s['label']])->all(),
        ]);
    }
}
