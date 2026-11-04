<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Results of one survey: overall and, for course feedback, per class and teacher.
 */
#[Layout('layouts::app')]
class SurveyResults extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $surveyId = '';

    public function mount(string $survey): void
    {
        $this->surveyId = $survey;
    }

    public function render(): View
    {
        $data = (array) $this->api()->withoutCache(fn () => $this->api()->get('surveys/'.$this->surveyId.'/results'))->data();

        return view('livewire.feedback.survey-results', ['data' => $data])->title($data['title']);
    }
}
