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

    /** Screen names used by dashboard cards, mapped to routes. */
    private const LINKS = [
        'announcements' => 'announcements.index', 'my-attendance' => 'my-attendance.show', 'my-fees' => 'my-fees.show', 'my-results' => 'my-results.show',
        'attendance' => 'attendance.take', 'my-mentees' => 'my-mentees.index', 'my-library' => 'my-library.show', 'students' => 'students.index',
        'reports' => 'reports.index', 'leave-approvals' => 'leave.approvals', 'applications' => 'applications.index', 'complaints' => 'complaints.index',
        'library-desk' => 'library.desk', 'maintenance' => 'maintenance.index',
    ];

    public function render(): View
    {
        $api = $this->api();
        $context = (array) $api->get('academic-context', memoize: true)->data();
        $notifications = $api->me()->notifications()->items;
        $events = $api->endpoint('calendar-events')->all(['from' => now()->toDateString(), 'to' => now()->addDays(30)->toDateString()]);

        $cards = collect((array) ($api->withoutCache(fn () => $api->get('me/dashboard'))->data()['cards'] ?? []))
            ->map(fn (array $card): array => [...$card, 'url' => isset(self::LINKS[$card['link'] ?? '']) ? route(self::LINKS[$card['link']]) : null])->all();

        return view('livewire.home', [
            'cards' => $cards,
            'term' => $context['term'] ?? null,
            'year' => $context['academic_year'] ?? null,
            'notifications' => array_slice($notifications, 0, 5),
            'events' => array_slice($events, 0, 6),
            'shortcuts' => array_slice(app(Navigation::class)->paletteItems($this->user()), 1, 8),
        ]);
    }
}
