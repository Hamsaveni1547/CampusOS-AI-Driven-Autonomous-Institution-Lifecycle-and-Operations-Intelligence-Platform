<?php

namespace App\Livewire\Exams;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Printable hall ticket for one exam cycle.
 */
#[Layout('layouts::app')]
class HallTicket extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $cycleId = '';

    public function mount(string $cycle): void
    {
        $this->cycleId = $cycle;
    }

    public function render(): View
    {
        $ticket = (array) $this->api()->get('me/hall-ticket/'.$this->cycleId)->data();

        return view('livewire.exams.hall-ticket', ['ticket' => $ticket])->title(__('Hall ticket'));
    }
}
