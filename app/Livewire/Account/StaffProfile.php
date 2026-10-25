<?php

namespace App\Livewire\Account;

use App\Api\Exceptions\NotFound;
use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * My staff record (read-only; HR maintains it).
 */
#[Layout('layouts::app')]
#[Title('Staff record')]
class StaffProfile extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        try {
            $employee = $this->api()->me()->employeeProfile();
        } catch (NotFound) {
            $employee = null;
        }

        return view('livewire.account.staff-profile', ['employee' => $employee]);
    }
}
