<?php

namespace App\Livewire\Exams;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The student's published results with grades, SGPA and CGPA, and revaluation requests.
 */
#[Layout('layouts::app')]
#[Title('My results')]
class MyResults extends Component
{
    use InteractsWithApi;

    /** @var array<string, string> */
    public array $request = ['registration_id' => '', 'course' => '', 'reason' => ''];

    public function openRevaluation(string $id, string $course): void
    {
        $this->request = ['registration_id' => $id, 'course' => $course, 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'revaluation-request');
    }

    public function requestRevaluation(): void
    {
        $this->validate(['request.reason' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('me/revaluation-requests', ['registration_id' => $this->request['registration_id'], 'reason' => $this->request['reason']]), fieldMap: ['reason' => 'request.reason'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'revaluation-request');
            $this->toast('success', __('Revaluation requested'));
        });
    }

    public function render(): View
    {
        return view('livewire.exams.my-results', ['data' => (array) $this->api()->get('me/results')->data()]);
    }
}
