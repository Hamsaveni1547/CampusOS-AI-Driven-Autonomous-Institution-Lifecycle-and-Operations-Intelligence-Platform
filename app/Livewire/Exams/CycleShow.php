<?php

namespace App\Livewire\Exams;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * One exam cycle: moving it through its stages, the timetable, registrations with eligibility, and results.
 */
#[Layout('layouts::app')]
class CycleShow extends Component
{
    use InteractsWithApi, WithTable;

    #[Locked]
    public string $cycleId = '';

    #[Url(except: 'timetable')]
    public string $tab = 'timetable';

    /** @var array<string, mixed> */
    public array $schedule = [];

    public ?string $overrideId = null;

    public string $reason = '';

    public function mount(string $cycle): void
    {
        $this->cycleId = $cycle;
    }

    protected function defaultFilters(): array
    {
        return ['eligibility' => ''];
    }

    public function step(string $action): void
    {
        $messages = [
            'open-registration' => __('Registration opened'), 'close-registration' => __('Registration closed and eligibility decided'),
            'eligibility' => __('Eligibility recalculated'), 'process' => __('Results processed'), 'verify' => __('Results verified'), 'publish' => __('Results published'),
        ];

        $this->callApi(fn () => $this->api()->post('exam-cycles/'.$this->cycleId.'/'.$action), onSuccess: fn () => $this->toast('success', $messages[$action] ?? __('Done')));
    }

    public function openSchedule(?string $id = null): void
    {
        $existing = $id ? collect($this->cycle()['schedules'])->firstWhere('id', $id) : null;
        $this->schedule = [
            'id' => $id, 'course_id' => $existing['course']['id'] ?? '', 'exam_date' => $existing['exam_date'] ?? '',
            'starts_at' => $existing['starts_at'] ?? '10:00', 'ends_at' => $existing['ends_at'] ?? '13:00', 'max_marks' => $existing['max_marks'] ?? 60,
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'schedule-form');
    }

    public function saveSchedule(): void
    {
        $this->validate(['schedule.course_id' => 'required|string', 'schedule.exam_date' => 'required|date', 'schedule.starts_at' => 'required|date_format:H:i', 'schedule.ends_at' => 'required|date_format:H:i', 'schedule.max_marks' => 'required|numeric|min:1']);
        $data = collect($this->schedule)->except('id')->all();
        $data['max_marks'] = (float) $data['max_marks'];

        $this->callApi(fn () => $this->schedule['id'] ? $this->api()->patch('exam-schedules/'.$this->schedule['id'], $data) : $this->api()->post('exam-cycles/'.$this->cycleId.'/schedules', $data),
            fieldMap: ['course_id' => 'schedule.course_id', 'exam_date' => 'schedule.exam_date', 'starts_at' => 'schedule.starts_at', 'ends_at' => 'schedule.ends_at', 'max_marks' => 'schedule.max_marks'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'schedule-form');
                $this->toast('success', __('Exam saved'));
            });
    }

    public function deleteSchedule(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('exam-schedules/'.$id), onSuccess: fn () => $this->toast('success', __('Exam removed')));
    }

    public function askOverride(string $id): void
    {
        $this->overrideId = $id;
        $this->reason = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'override');
    }

    public function override(): void
    {
        $this->validate(['reason' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('exam-registrations/'.$this->overrideId.'/override', ['reason' => $this->reason]), fieldMap: ['reason' => 'reason'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'override');
            $this->toast('success', __('Student made eligible'));
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function cycle(): array
    {
        return (array) $this->api()->get('exam-cycles/'.$this->cycleId)->data();
    }

    public function render(): View
    {
        $cycle = $this->cycle();
        $options = (array) $this->api()->get('exams/options', memoize: true)->data();

        return view('livewire.exams.cycle-show', [
            'cycle' => $cycle,
            'statuses' => collect($options['statuses'])->mapWithKeys(fn (array $t) => [$t['value'] => $t['label']])->all(),
            'rows' => $this->tab === 'registrations' ? $this->api()->endpoint('exam-cycles/'.$this->cycleId.'/registrations')->page($this->activeFilters(), $this->tableQuery()) : null,
            'results' => $this->tab === 'results' && in_array($cycle['status'], ['results_processed', 'results_verified', 'published'], true) ? (array) $this->api()->get('exam-cycles/'.$this->cycleId.'/results')->data() : null,
            'courses' => $cycle['can']['manage'] ? app(Lookups::class)->courses() : [],
        ])->title($cycle['name']);
    }
}
