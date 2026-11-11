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

    /** Picker: step 1 section (batch + section name), step 2 subject (class id), and the date. */
    public string $pickSection = '';

    public string $pickClass = '';

    public string $pickDate = '';

    public function mount(): void
    {
        $this->pickDate = now()->toDateString();
        $this->loadSheet();
    }

    public function updatedPickSection(): void
    {
        $this->pickClass = '';
        $classes = $this->classesIn($this->pickSection);

        if (count($classes) === 1) {
            $this->pickClass = (string) array_key_first($classes);
        }
    }

    /**
     * Opens the student list for the chosen section, subject and date.
     */
    public function startPicked(): void
    {
        $this->validate(['pickSection' => 'required|string', 'pickClass' => 'required|string', 'pickDate' => 'required|date|before_or_equal:today'], [], ['pickSection' => __('section'), 'pickClass' => __('subject'), 'pickDate' => __('date')]);
        $this->open($this->pickClass, $this->pickDate);
    }

    /**
     * @return list<array<string, mixed>> the teacher's classes this term
     */
    private function myClasses(): array
    {
        return (array) ($this->api()->get('me/teaching', memoize: true)->data()['classes'] ?? []);
    }

    /**
     * @return array<string, string> class id => "code · title" for one section
     */
    private function classesIn(string $section): array
    {
        return collect($this->myClasses())->filter(fn (array $c) => $c['section']['batch'].' '.$c['section']['name'] === $section)
            ->mapWithKeys(fn (array $c) => [$c['id'] => $c['course']['code'].' · '.$c['course']['title']])->all();
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

        $sections = $sheet === null ? collect($this->myClasses())->map(fn (array $c) => $c['section']['batch'].' '.$c['section']['name'])->unique()->sort()->mapWithKeys(fn (string $s) => [$s => $s])->all() : [];

        return view('livewire.attendance.take', [
            'sections' => $sections,
            'subjects' => $this->pickSection !== '' ? $this->classesIn($this->pickSection) : [],
            'pending' => $sheet === null ? (array) $this->api()->withoutCache(fn () => $this->api()->get('me/attendance/pending'))->data() : [],
            'sheet' => $sheet,
            'options' => collect($options['statuses'])->mapWithKeys(fn (array $s) => [$s['value'] => $s['label']])->all(),
            'counts' => array_count_values($this->statuses),
        ]);
    }
}
