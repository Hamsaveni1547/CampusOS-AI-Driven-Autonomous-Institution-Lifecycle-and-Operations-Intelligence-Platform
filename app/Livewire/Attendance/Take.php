<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Teacher's attendance desk: classes still to mark, and the sheet for one class meeting.
 */
#[Layout('layouts::app')]
#[Title('Take attendance')]
class Take extends Component
{
    use InteractsWithApi;

    #[Url(as: 'class', except: '')]
    public string $class = '';

    #[Url(as: 'date', except: '')]
    public string $date = '';

    #[Url(as: 'slot', except: '')]
    public string $slot = '';

    /** @var array<string, string> student id => status */
    public array $statuses = [];

    public string $notes = '';

    public function mount(): void
    {
        $this->loadSheet();
    }

    public function open(string $class, string $date, ?string $slot = null): void
    {
        $this->class = $class;
        $this->date = $date;
        $this->slot = (string) $slot;
        $this->loadSheet();
    }

    public function close(): void
    {
        $this->reset('class', 'date', 'slot', 'statuses', 'notes');
    }

    /** @return array<string, mixed>|null */
    private function sheet(): ?array
    {
        if ($this->class === '' || $this->date === '') {
            return null;
        }

        return (array) $this->api()->withoutCache(fn () => $this->api()->get('attendance/sheet', array_filter(['class' => $this->class, 'date' => $this->date, 'slot' => $this->slot])))->data();
    }

    private function loadSheet(): void
    {
        $sheet = $this->sheet();
        $this->statuses = collect($sheet['students'] ?? [])->mapWithKeys(fn (array $s) => [$s['student_id'] => $s['status'] ?? 'present'])->all();
        $this->notes = (string) ($sheet['session']['notes'] ?? '');
    }

    public function setAll(string $status): void
    {
        $this->statuses = array_map(fn () => $status, $this->statuses);
    }

    public function save(): void
    {
        $this->callApi(fn () => $this->api()->post('attendance/sheet', [
            'class_id' => $this->class,
            'date' => $this->date,
            'time_slot_id' => $this->slot ?: null,
            'notes' => $this->notes ?: null,
            'statuses' => collect($this->statuses)->reject(fn (string $status) => $status === 'present')->all(),
        ]), onSuccess: function ($response): void {
            $this->toast('success', (string) $response->data('message'));
            $this->close();
        });
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('attendance/options', memoize: true)->data();
        $sheet = $this->sheet();

        return view('livewire.attendance.take', [
            'pending' => $sheet === null ? (array) $this->api()->withoutCache(fn () => $this->api()->get('me/attendance/pending'))->data() : [],
            'sheet' => $sheet,
            'options' => collect($options['statuses'])->mapWithKeys(fn (array $s) => [$s['value'] => $s['label']])->all(),
            'counts' => array_count_values($this->statuses),
        ]);
    }
}
