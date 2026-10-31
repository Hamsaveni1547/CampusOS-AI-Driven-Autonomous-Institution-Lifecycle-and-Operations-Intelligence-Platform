<?php

namespace App\Livewire\Exams;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The student's upcoming exams: courses, eligibility, timetable, backlog registration and hall tickets.
 */
#[Layout('layouts::app')]
#[Title('My exams')]
class MyExams extends Component
{
    use InteractsWithApi;

    /** @var array<string, array<int, string>> cycle id => backlog course ids */
    public array $backlogs = [];

    public function registerBacklogs(string $cycleId): void
    {
        $courses = array_values($this->backlogs[$cycleId] ?? []);

        if ($courses === []) {
            $this->toast('warning', __('Choose at least one course'));

            return;
        }

        $this->callApi(fn () => $this->api()->post('me/exam-cycles/'.$cycleId.'/backlogs', ['course_ids' => $courses]), onSuccess: function () use ($cycleId): void {
            unset($this->backlogs[$cycleId]);
            $this->toast('success', __('Registered for the exam'));
        });
    }

    public function render(): View
    {
        return view('livewire.exams.my-exams', ['cycles' => (array) $this->api()->get('me/exams')->data('cycles', [])]);
    }
}
