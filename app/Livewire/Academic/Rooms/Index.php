<?php

namespace App\Livewire\Academic\Rooms;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\ManagesRecords;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Rooms')]
class Index extends Component
{
    use InteractsWithApi, ManagesRecords, WithTable;

    /** @return array<string, string> */
    public static function types(): array
    {
        return ['classroom' => __('Classroom'), 'laboratory' => __('Laboratory'), 'seminar_hall' => __('Seminar hall'), 'exam_hall' => __('Exam hall'), 'library' => __('Library'), 'staff_room' => __('Staff room'), 'office' => __('Office'), 'other' => __('Other')];
    }

    protected function defaultFilters(): array
    {
        return ['type' => '', 'department' => '', 'building' => '', 'status' => '', 'min_capacity' => ''];
    }

    protected function recordsPath(): string
    {
        return 'rooms';
    }

    protected function recordLabel(): string
    {
        return __('Room');
    }

    protected function emptyForm(): array
    {
        return ['code' => '', 'name' => '', 'building' => '', 'floor' => '', 'type' => 'classroom', 'capacity' => '', 'status' => 'active', 'department_id' => ''];
    }

    protected function toForm(array $record): array
    {
        return [
            'code' => $record['code'],
            'name' => (string) $record['name'],
            'building' => (string) $record['building'],
            'floor' => (string) $record['floor'],
            'type' => $record['type'],
            'capacity' => (string) $record['capacity'],
            'status' => $record['status'],
            'department_id' => (string) ($record['department']['id'] ?? ''),
        ];
    }

    protected function toPayload(): array
    {
        $data = $this->nullBlanks($this->form);
        $data['capacity'] = (int) $data['capacity'];

        return $data;
    }

    protected function formRules(): array
    {
        return [
            'form.code' => 'required|string|max:30',
            'form.type' => 'required|string',
            'form.capacity' => 'required|integer|min:0|max:5000',
        ];
    }

    public function render(): View
    {
        $departments = app(Lookups::class)->departments(false);

        return view('livewire.academic.rooms.index', [
            'rows' => $this->api()->endpoint('rooms')->page($this->activeFilters(), $this->tableQuery()),
            'departments' => $departments,
            'types' => self::types(),
            'canManage' => $this->can('institution.rooms.manage'),
            'chips' => $this->filterChips([
                'type' => ['_name' => __('Type')] + self::types(),
                'department' => ['_name' => __('Department')] + $departments,
                'building' => __('Building'),
                'status' => ['_name' => __('Status'), 'active' => __('Active'), 'inactive' => __('Inactive')],
                'min_capacity' => __('Seats at least'),
            ]),
        ]);
    }
}
