<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Course feedback for the classes the signed-in teacher teaches (aggregated, anonymous).
 */
#[Layout('layouts::app')]
#[Title('My feedback')]
class TeachingFeedback extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        return view('livewire.feedback.teaching-feedback', ['surveys' => (array) $this->api()->get('me/teaching-feedback')->data()]);
    }
}
