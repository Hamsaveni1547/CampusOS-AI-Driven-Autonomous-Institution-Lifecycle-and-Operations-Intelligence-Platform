<?php

namespace App\Livewire\Academics\Courses;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One course with its syllabus. The syllabus editor lists topics one per line under each unit.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $courseId = '';

    /** @var list<array{title: string, hours: string, topics: string}> */
    public array $units = [];

    public string $description = '';

    public string $textbooks = '';

    public function mount(string $course): void
    {
        $this->courseId = $course;
    }

    public function editSyllabus(): void
    {
        $course = $this->api()->endpoint('courses')->find($this->courseId);
        $this->units = collect($course['units'])->map(fn (array $unit) => [
            'title' => $unit['title'],
            'hours' => (string) $unit['hours'],
            'topics' => collect($unit['topics'])->pluck('title')->implode("\n"),
        ])->values()->all();

        if ($this->units === []) {
            $this->addUnit();
        }

        $this->description = (string) $course['description'];
        $this->textbooks = implode("\n", $course['textbooks']);
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'syllabus-form');
    }

    public function addUnit(): void
    {
        $this->units[] = ['title' => '', 'hours' => '', 'topics' => ''];
    }

    public function removeUnit(int $index): void
    {
        unset($this->units[$index]);
        $this->units = array_values($this->units);
    }

    public function saveSyllabus(): void
    {
        $this->validate(['units.*.title' => 'required|string|max:200', 'units.*.hours' => 'nullable|integer|min:0|max:200']);

        $lines = fn (string $text): array => collect(preg_split('/\r\n|\r|\n/', $text) ?: [])->map(fn (string $line) => trim($line))->filter()->values()->all();

        $payload = [
            'units' => collect($this->units)->map(fn (array $unit) => [
                'title' => $unit['title'],
                'hours' => $unit['hours'] !== '' ? (int) $unit['hours'] : null,
                'topics' => collect($lines($unit['topics']))->map(fn (string $title) => ['title' => $title])->all(),
            ])->all(),
            'description' => $this->description ?: null,
            'textbooks' => $lines($this->textbooks),
        ];

        $this->callApi(fn () => $this->api()->put('courses/'.$this->courseId.'/syllabus', $payload), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'syllabus-form');
            $this->toast('success', __('Syllabus saved'));
        });
    }

    public function render(): View
    {
        $course = $this->api()->endpoint('courses')->find($this->courseId);

        return view('livewire.academics.courses.show', [
            'course' => $course,
            'canManage' => $this->can('academics.courses.manage'),
        ])->title($course['code'].' '.$course['title']);
    }
}
