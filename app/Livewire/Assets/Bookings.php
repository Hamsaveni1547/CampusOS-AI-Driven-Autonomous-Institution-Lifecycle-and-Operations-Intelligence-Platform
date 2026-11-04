<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Upcoming lab and room bookings, and booking a room.
 */
#[Layout('layouts::app')]
#[Title('Room bookings')]
class Bookings extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, string> */
    public array $form = ['room_id' => '', 'date' => '', 'starts_at' => '', 'ends_at' => '', 'purpose' => ''];

    protected function defaultFilters(): array
    {
        return ['room' => '', 'mine' => ''];
    }

    public function open(): void
    {
        $this->form = ['room_id' => '', 'date' => now()->addDay()->toDateString(), 'starts_at' => '14:00', 'ends_at' => '16:00', 'purpose' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'book-room');
    }

    public function book(): void
    {
        $this->validate(['form.room_id' => 'required|string', 'form.date' => 'required|date', 'form.starts_at' => 'required|date_format:H:i', 'form.ends_at' => 'required|date_format:H:i', 'form.purpose' => 'required|string|max:300']);

        $this->callApi(fn () => $this->api()->post('room-bookings', $this->form), fieldMap: ['room_id' => 'form.room_id', 'date' => 'form.date', 'purpose' => 'form.purpose'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'book-room');
            $this->toast('success', __('Room booked'));
        });
    }

    public function cancel(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('room-bookings/'.$id.'/cancel'), onSuccess: fn () => $this->toast('success', __('Booking cancelled')));
    }

    public function render(): View
    {
        return view('livewire.assets.bookings', [
            'rows' => $this->api()->endpoint('room-bookings')->page($this->activeFilters(null), $this->tableQuery()),
            'rooms' => app(Lookups::class)->rooms(),
        ]);
    }
}
