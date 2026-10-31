<?php

namespace App\Livewire\Exams;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * External marks for one exam, entered against answer-script codes only.
 */
#[Layout('layouts::app')]
class MarksEntry extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $scheduleId = '';

    /** @var array<string, array{attendance: string, marks: string|float|null}> */
    public array $entries = [];

    public function mount(string $schedule): void
    {
        $this->scheduleId = $schedule;
        $this->entries = collect($this->data()['scripts'])->mapWithKeys(fn (array $s) => [$s['code'] => ['attendance' => $s['attendance'] ?? 'present', 'marks' => $s['marks']]])->all();
    }

    public function save(): void
    {
        $this->validate(['entries.*.attendance' => 'required|in:present,absent', 'entries.*.marks' => 'nullable|numeric|min:0']);
        $marks = collect($this->entries)
            ->filter(fn (array $e) => $e['attendance'] === 'absent' || ($e['marks'] !== null && $e['marks'] !== ''))
            ->map(fn (array $e) => $e['attendance'] === 'absent' ? ['attendance' => 'absent'] : ['attendance' => 'present', 'marks' => (float) $e['marks']])
            ->all();

        if ($marks === []) {
            $this->toast('warning', __('Nothing to save yet'));

            return;
        }

        $this->callApi(fn () => $this->api()->put('exam-schedules/'.$this->scheduleId.'/marks', ['marks' => $marks]), onSuccess: fn () => $this->toast('success', __(':n scripts saved', ['n' => count($marks)])));
    }

    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        return (array) $this->api()->withoutCache(fn () => $this->api()->get('exam-schedules/'.$this->scheduleId.'/scripts'))->data();
    }

    public function render(): View
    {
        $data = $this->data();

        return view('livewire.exams.marks-entry', [
            'exam' => $data,
            'entered' => collect($data['scripts'])->whereNotNull('attendance')->count(),
        ])->title($data['course']['code'].' '.__('marks'));
    }
}
