<?php

namespace App\Livewire\Platform\Access;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The built-in roles shared by every institution.
 */
#[Layout('layouts::app')]
#[Title('Roles & permissions')]
class Roles extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        return view('livewire.platform.access.roles', ['roles' => (array) $this->api()->withoutCache(fn () => $this->api()->get('platform/access/roles'))->data()]);
    }
}
