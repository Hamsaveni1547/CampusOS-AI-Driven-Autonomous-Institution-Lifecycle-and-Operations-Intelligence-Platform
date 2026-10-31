<?php

namespace App\Livewire\Admissions\Cycles;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One admission cycle: seat matrix and opening/closing.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $cycleId = '';

    public ?string $seatId = null;

    /** @var array<string, string> */
    public array $seatForm = ['programme_id' => '', 'quota' => '', 'total' => '', 'min_percentage' => '', 'admission_fee' => ''];

    public ?string $deleteSeatId = null;

    public function mount(string $cycle): void
    {
        $this->cycleId = $cycle;
    }

    public function editSeat(?string $seatId = null): void
    {
        $this->seatId = $seatId;
        $this->seatForm = ['programme_id' => '', 'quota' => '', 'total' => '', 'min_percentage' => '', 'admission_fee' => ''];

        if ($seatId !== null) {
            $seat = collect($this->cycle()['seats'] ?? [])->firstWhere('id', $seatId);
            $this->seatForm = [
                'programme_id' => (string) $seat['programme']['id'],
                'quota' => $seat['quota'],
                'total' => (string) $seat['total'],
                'min_percentage' => (string) $seat['min_percentage'],
                'admission_fee' => (string) $seat['admission_fee'],
            ];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'seat-form');
    }

    public function saveSeat(): void
    {
        $this->validate([
            'seatForm.programme_id' => $this->seatId ? 'nullable' : 'required|string',
            'seatForm.quota' => $this->seatId ? 'nullable' : 'required|string',
            'seatForm.total' => 'required|integer|min:1|max:5000',
            'seatForm.min_percentage' => 'nullable|numeric|min:0|max:100',
            'seatForm.admission_fee' => 'nullable|numeric|min:0',
        ]);

        $data = [
            'total' => (int) $this->seatForm['total'],
            'min_percentage' => $this->seatForm['min_percentage'] !== '' ? (float) $this->seatForm['min_percentage'] : null,
            'admission_fee' => (float) ($this->seatForm['admission_fee'] ?: 0),
        ];

        $this->callApi(
            fn () => $this->seatId
                ? $this->api()->patch('admission-seats/'.$this->seatId, $data)
                : $this->api()->post('admission-cycles/'.$this->cycleId.'/seats', $data + ['programme_id' => $this->seatForm['programme_id'], 'quota' => $this->seatForm['quota']]),
            fieldMap: collect(array_keys($this->seatForm))->mapWithKeys(fn ($key) => [$key => 'seatForm.'.$key])->all(),
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'seat-form');
                $this->toast('success', __('Seats saved'));
            },
        );
    }

    public function askDeleteSeat(string $seatId): void
    {
        $this->deleteSeatId = $seatId;
        $this->dispatch('open-dialog', 'delete-seat');
    }

    public function deleteSeat(): void
    {
        $this->callApi(fn () => $this->api()->delete('admission-seats/'.$this->deleteSeatId), onSuccess: function (): void {
            $this->deleteSeatId = null;
            $this->dispatch('close-dialog', 'delete-seat');
            $this->toast('success', __('Seat row removed'));
        });
    }

    public function setOpen(bool $open): void
    {
        $this->callApi(
            fn () => $this->api()->post('admission-cycles/'.$this->cycleId.'/'.($open ? 'open' : 'close')),
            onSuccess: fn () => $this->toast('success', $open ? __('Applications are open') : __('Cycle closed')),
        );
    }

    /** @return array<string, mixed> */
    private function cycle(): array
    {
        return $this->api()->endpoint('admission-cycles')->find($this->cycleId);
    }

    public function render(): View
    {
        $cycle = $this->cycle();
        $lookups = app(Lookups::class);

        return view('livewire.admissions.cycles.show', [
            'cycle' => $cycle,
            'quotas' => $lookups->admissionOptions('quotas'),
            'programmes' => $lookups->programmes(),
            'totals' => ['seats' => collect($cycle['seats'])->sum('total'), 'filled' => collect($cycle['seats'])->sum('filled')],
        ])->title($cycle['name']);
    }
}
