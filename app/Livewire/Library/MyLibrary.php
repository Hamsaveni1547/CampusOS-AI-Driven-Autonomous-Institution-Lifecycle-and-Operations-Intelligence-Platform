<?php

namespace App\Livewire\Library;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The member's books on loan (with renewal), history and fines.
 */
#[Layout('layouts::app')]
#[Title('My library')]
class MyLibrary extends Component
{
    use InteractsWithApi;

    public function renew(string $loanId): void
    {
        $this->callApi(fn () => $this->api()->post('library/loans/'.$loanId.'/renew'), onSuccess: fn ($response) => $this->toast('success', __('Renewed; now due :d', ['d' => $response->data('due_on')])));
    }

    public function render(): View
    {
        return view('livewire.library.my-library', [
            'data' => (array) $this->api()->withoutCache(fn () => $this->api()->get('me/library'))->data(),
            'rules' => (array) $this->api()->get('library/options', memoize: true)->data(),
        ]);
    }
}
