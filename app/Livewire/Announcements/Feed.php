<?php

namespace App\Livewire\Announcements;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Announcements sent to me: pinned first, then newest.
 */
#[Layout('layouts::app')]
#[Title('Announcements')]
class Feed extends Component
{
    use InteractsWithApi;

    #[Url(except: false)]
    public bool $unread = false;

    public function render(): View
    {
        return view('livewire.announcements.feed', [
            'items' => (array) $this->api()->withoutCache(fn () => $this->api()->get('me/announcements', ['per_page' => 50, 'filter' => array_filter(['unread' => $this->unread ? 1 : null])]))->data(),
        ]);
    }
}
