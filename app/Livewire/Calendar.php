<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Academic calendar by month: holidays, exams, deadlines and events.
 */
#[Layout('layouts::app')]
#[Title('Academic calendar')]
class Calendar extends Component
{
    use InteractsWithApi;

    /**
     * Month shown, "YYYY-MM".
     */
    #[Url(as: 'month')]
    public string $month = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    public function mount(): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $this->month) !== 1) {
            $this->month = now()->format('Y-m');
        }
    }

    public function previous(): void
    {
        $this->month = $this->start()->subMonth()->format('Y-m');
    }

    public function next(): void
    {
        $this->month = $this->start()->addMonth()->format('Y-m');
    }

    public function today(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function render(): View
    {
        $start = $this->start();
        $end = $start->copy()->endOfMonth();

        $events = $this->api()->endpoint('calendar-events')->all(array_filter([
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'type' => $this->type,
        ]));

        // Group by every day the event covers within this month.
        $days = [];
        foreach ($events as $event) {
            $day = Carbon::parse($event['starts_on'])->max($start);
            $last = Carbon::parse($event['ends_on'])->min($end);

            while ($day->lte($last)) {
                $days[$day->toDateString()][] = $event;
                $day = $day->copy()->addDay();
            }
        }

        ksort($days);

        return view('livewire.calendar', [
            'start' => $start,
            'days' => $days,
            'grid' => $this->grid($start),
            'types' => ['holiday' => __('Holidays'), 'examination' => __('Examinations'), 'deadline' => __('Deadlines'), 'event' => __('Events'), 'term_start' => __('Term start'), 'term_end' => __('Term end')],
        ]);
    }

    private function start(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->month.'-01')?->startOfDay() ?? now()->startOfMonth();
    }

    /**
     * Weeks (Mon–Sun) covering the month, as date strings.
     *
     * @return array<int, array<int, Carbon>>
     */
    private function grid(Carbon $start): array
    {
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $last = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];

        while ($cursor->lte($last)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $cursor->copy();
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }
}
