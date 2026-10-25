<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Navigation;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Signed-in home: greeting, current term, account checks, latest notifications,
 * upcoming calendar events and shortcuts to the user's areas.
 */
#[Layout('layouts::app')]
#[Title('Home')]
class Home extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        $api = $this->api();
        $context = (array) $api->get('academic-context', memoize: true)->data();
        $notifications = $api->me()->notifications()->items;
        $events = $api->endpoint('calendar-events')->all(['from' => now()->toDateString(), 'to' => now()->addDays(30)->toDateString()]);

        return view('livewire.home', [
            'term' => $context['term'] ?? null,
            'year' => $context['academic_year'] ?? null,
            'notifications' => array_slice($notifications, 0, 5),
            'events' => array_slice($events, 0, 6),
            'shortcuts' => array_slice(app(Navigation::class)->paletteItems($this->user()), 1, 8),
        ]);
    }
}
