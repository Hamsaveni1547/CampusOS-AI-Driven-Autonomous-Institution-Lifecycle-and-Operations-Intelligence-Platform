<?php

namespace App\Livewire\Admissions\MyApplications;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Applicant home for admissions: own applications and cycles open to apply.
 */
#[Layout('layouts::app')]
#[Title('My applications')]
class Index extends Component
{
    use InteractsWithApi;

    public function apply(string $seatId): void
    {
        $response = $this->callApi(fn () => $this->api()->post('me/applications', ['seat_id' => $seatId]));

        if ($response !== null) {
            $this->redirectRoute('my-applications.show', $response->data('id'), navigate: true);
        }
    }

    public function render(): View
    {
        $data = $this->api()->getMany(['mine' => 'me/applications', 'open' => 'admissions/cycles/open']);

        return view('livewire.admissions.my-applications.index', [
            'applications' => (array) $data['mine']->data(),
            'cycles' => (array) $data['open']->data(),
            'quotas' => app(Lookups::class)->admissionOptions('quotas'),
        ]);
    }
}
