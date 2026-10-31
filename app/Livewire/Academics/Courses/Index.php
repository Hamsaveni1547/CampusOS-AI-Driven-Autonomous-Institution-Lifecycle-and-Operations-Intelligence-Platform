<?php

namespace App\Livewire\Academics\Courses;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\ManagesRecords;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Courses')]
class Index extends Component
{
    use InteractsWithApi, ManagesRecords, WithTable;

    protected function defaultFilters(): array
    {
        return ['department' => '', 'type' => '', 'status' => ''];
    }

    protected function recordsPath(): string
    {
        return 'courses';
    }

    protected function recordLabel(): string
    {
        return __('Course');
    }

    protected function emptyForm(): array
    {
        return ['department_id' => '', 'code' => '', 'title' => '', 'type' => '', 'credits' => '', 'lecture_hours' => '0', 'tutorial_hours' => '0', 'practical_hours' => '0', 'regulation' => '', 'status' => 'active'];
    }

    protected function toForm(array $record): array
    {
        return array_map(fn ($value) => (string) $value, array_intersect_key($record, $this->emptyForm())) + ['department_id' => (string) ($record['department']['id'] ?? '')];
    }

    protected function toPayload(): array
    {
        $data = $this->nullBlanks($this->form);

        foreach (['lecture_hours', 'tutorial_hours', 'practical_hours'] as $hours) {
            $data[$hours] = (int) ($data[$hours] ?? 0);
        }

        $data['credits'] = (float) $data['credits'];

        return $data;
    }

    protected function formRules(): array
    {
        return [
            'form.department_id' => 'required|string',
            'form.code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_.]+$/'],
            'form.title' => 'required|string|max:200',
            'form.type' => 'required|string',
            'form.credits' => 'required|numeric|min:0|max:30',
            'form.lecture_hours' => 'nullable|integer|min:0|max:20',
            'form.tutorial_hours' => 'nullable|integer|min:0|max:20',
            'form.practical_hours' => 'nullable|integer|min:0|max:20',
        ];
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $types = $lookups->academicsOptions('course_types');
        $departments = $lookups->departments(false);

        return view('livewire.academics.courses.index', [
            'rows' => $this->api()->endpoint('courses')->page($this->activeFilters(), $this->tableQuery()),
            'types' => $types,
            'departments' => $departments,
            'canManage' => $this->can('academics.courses.manage'),
            'chips' => $this->filterChips([
                'department' => ['_name' => __('Department')] + $departments,
                'type' => ['_name' => __('Type')] + $types,
                'status' => ['_name' => __('Status'), 'active' => __('Active'), 'inactive' => __('Inactive')],
            ]),
        ]);
    }
}
