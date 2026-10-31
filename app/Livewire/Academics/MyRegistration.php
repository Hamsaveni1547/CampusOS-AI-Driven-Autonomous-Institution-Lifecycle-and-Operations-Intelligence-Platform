<?php

namespace App\Livewire\Academics;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Student term registration: core courses are fixed, electives are chosen.
 */
#[Layout('layouts::app')]
#[Title('Term registration')]
class MyRegistration extends Component
{
    use InteractsWithApi;

    /** @var array<int, string> chosen elective offering ids (checkbox arrays can have gaps) */
    public array $electives = [];

    public function mount(): void
    {
        $data = $this->data();
        $this->electives = collect($data['registration']['courses'] ?? [])->where('type', 'elective')->pluck('offering_id')->values()->all();
    }

    /** @return array<string, mixed> */
    private function data(): array
    {
        return (array) $this->api()->get('me/term-registration')->data();
    }

    public function register(): void
    {
        $this->callApi(fn () => $this->api()->post('me/term-registration', ['elective_offering_ids' => array_values($this->electives)]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'confirm-registration');
            $this->toast('success', __('Registration submitted'), __('Your department will review it.'));
        });
    }

    public function render(): View
    {
        $data = $this->data();
        $credits = collect($data['core'])->sum('course.credits') + collect($data['electives'])->whereIn('offering_id', $this->electives)->sum('course.credits');

        return view('livewire.academics.my-registration', ['data' => $data, 'credits' => $credits]);
    }
}
