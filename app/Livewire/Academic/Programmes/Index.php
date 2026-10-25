<?php

namespace App\Livewire\Academic\Programmes;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\ManagesRecords;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Programmes')]
class Index extends Component
{
    use InteractsWithApi, ManagesRecords, WithTable;

    /** @return array<string, string> */
    public static function levels(): array
    {
        return ['ug' => __('Undergraduate'), 'pg' => __('Postgraduate'), 'diploma' => __('Diploma'), 'doctoral' => __('Doctoral'), 'certificate' => __('Certificate')];
    }

    protected function defaultFilters(): array
    {
        return ['department' => '', 'level' => '', 'status' => ''];
    }

    protected function recordsPath(): string
    {
        return 'programmes';
    }

    protected function recordLabel(): string
    {
        return __('Programme');
    }

    protected function emptyForm(): array
    {
        return ['department_id' => '', 'code' => '', 'name' => '', 'level' => 'ug', 'duration_years' => '4', 'total_terms' => '', 'intake_capacity' => '', 'regulation' => '', 'status' => 'active'];
    }

    protected function toForm(array $record): array
    {
        return [
            'department_id' => $record['department']['id'] ?? '',
            'code' => $record['code'],
            'name' => $record['name'],
            'level' => $record['level'],
            'duration_years' => (string) $record['duration_years'],
            'total_terms' => (string) $record['total_terms'],
            'intake_capacity' => (string) $record['intake_capacity'],
            'regulation' => (string) $record['regulation'],
            'status' => $record['status'],
        ];
    }

    protected function toPayload(): array
    {
        $data = $this->nullBlanks($this->form);

        foreach (['duration_years', 'total_terms', 'intake_capacity'] as $number) {
            $data[$number] = $data[$number] !== null ? (int) $data[$number] : null;
        }

        if ($data['total_terms'] === null) {
            unset($data['total_terms']);
        }

        return $data;
    }

    protected function formRules(): array
    {
        return [
            'form.department_id' => 'required|string',
            'form.code' => 'required|string|max:20|alpha_dash',
            'form.name' => 'required|string|max:200',
            'form.level' => 'required|string',
            'form.duration_years' => 'required|integer|min:1|max:7',
            'form.total_terms' => 'nullable|integer|min:1|max:21',
            'form.intake_capacity' => 'nullable|integer|min:1|max:5000',
        ];
    }

    public function render(): View
    {
        $departments = app(Lookups::class)->departments(false);

        return view('livewire.academic.programmes.index', [
            'programmes' => $this->api()->endpoint('programmes')->all($this->activeFilters(), memoize: false),
            'departments' => $departments,
            'levels' => self::levels(),
            'canManage' => $this->can('institution.programmes.manage'),
            'chips' => $this->filterChips([
                'department' => ['_name' => __('Department')] + $departments,
                'level' => ['_name' => __('Level')] + self::levels(),
                'status' => ['_name' => __('Status'), 'active' => __('Active'), 'inactive' => __('Inactive')],
            ]),
        ]);
    }
}
