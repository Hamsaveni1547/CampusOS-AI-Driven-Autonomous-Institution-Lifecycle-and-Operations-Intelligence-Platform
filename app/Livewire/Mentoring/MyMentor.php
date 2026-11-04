<?php

namespace App\Livewire\Mentoring;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The student's mentor, meetings, and asking for a meeting.
 */
#[Layout('layouts::app')]
#[Title('My mentor')]
class MyMentor extends Component
{
    use InteractsWithApi;

    public string $agenda = '';

    public function request(): void
    {
        $this->validate(['agenda' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('me/mentor/meeting-requests', ['agenda' => $this->agenda]), fieldMap: ['agenda' => 'agenda'], onSuccess: function (): void {
            $this->agenda = '';
            $this->toast('success', __('Request sent to your mentor'));
        });
    }

    public function render(): View
    {
        return view('livewire.mentoring.my-mentor', ['data' => (array) $this->api()->withoutCache(fn () => $this->api()->get('me/mentor'))->data()]);
    }
}
