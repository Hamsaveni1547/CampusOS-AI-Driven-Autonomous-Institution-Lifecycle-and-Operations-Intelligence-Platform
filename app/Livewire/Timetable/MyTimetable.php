<?php

namespace App\Livewire\Timetable;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The signed-in student's or teacher's timetable: a chosen day (with changes) and the week.
 */
#[Layout('layouts::app')]
#[Title('My timetable')]
class MyTimetable extends Component
{
    use InteractsWithApi;

    #[Url(as: 'date', except: '')]
    public string $date = '';

    /** Private calendar-feed link, shown after "Add to my calendar". */
    public string $calendarUrl = '';

    public function calendarLink(): void
    {
        $this->callApi(fn () => $this->api()->get('me/timetable/calendar-link'), onSuccess: fn ($response) => $this->calendarUrl = (string) $response->data('url'));
    }

    public function shiftDay(int $by): void
    {
        $this->date = Carbon::parse($this->date !== '' ? $this->date : now()->toDateString())->addDays($by)->toDateString();
    }

    public function render(): View
    {
        $data = (array) $this->api()->withoutCache(fn () => $this->api()->get('me/timetable', array_filter(['date' => $this->date])))->data();

        return view('livewire.timetable.my-timetable', [
            'data' => $data,
            'dayLabel' => Carbon::parse($data['date'])->translatedFormat('l, d M Y'),
        ]);
    }
}
