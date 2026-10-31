<?php

namespace App\Livewire\Timetable;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Day × period grid for one section. Clicking a cell places or edits a class; clashes are refused by the API.
 */
#[Layout('layouts::app')]
class Builder extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $timetableId = '';

    public ?string $entryId = null;

    /** @var array<string, mixed> */
    public array $cell = ['day_of_week' => 1, 'time_slot_id' => '', 'offering_section_id' => '', 'employee_id' => '', 'room_id' => '', 'component' => 'theory'];

    public function mount(string $timetable): void
    {
        $this->timetableId = $timetable;
    }

    /** @return array<string, mixed> */
    private function timetable(): array
    {
        return (array) $this->api()->get('timetables/'.$this->timetableId)->data();
    }

    public function openCell(int $day, string $slotId): void
    {
        $existing = collect($this->timetable()['entries'])->first(fn (array $entry) => $entry['day_of_week'] === $day && $entry['slot_id'] === $slotId);
        $this->entryId = $existing['id'] ?? null;
        $this->cell = [
            'day_of_week' => $day,
            'time_slot_id' => $slotId,
            'offering_section_id' => (string) ($existing['class_id'] ?? ''),
            'employee_id' => (string) ($existing['teacher']['id'] ?? ''),
            'room_id' => (string) ($existing['room']['id'] ?? ''),
            'component' => (string) ($existing['component'] ?? 'theory'),
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'cell-form');
    }

    public function updatedCellOfferingSectionId(): void
    {
        $this->cell['employee_id'] = '';
    }

    public function saveCell(): void
    {
        $this->validate(['cell.offering_section_id' => 'required|string', 'cell.component' => 'required|in:theory,lab,tutorial']);

        $data = [
            'day_of_week' => (int) $this->cell['day_of_week'],
            'time_slot_id' => $this->cell['time_slot_id'],
            'offering_section_id' => $this->cell['offering_section_id'],
            'room_id' => $this->cell['room_id'] ?: null,
            'component' => $this->cell['component'],
        ];

        if ($this->cell['employee_id'] !== '') {
            $data['employee_id'] = $this->cell['employee_id'];
        }

        $this->callApi(
            fn () => $this->entryId ? $this->api()->patch('timetable-entries/'.$this->entryId, $data) : $this->api()->post('timetables/'.$this->timetableId.'/entries', $data),
            fieldMap: ['offering_section_id' => 'cell.offering_section_id', 'employee_id' => 'cell.employee_id', 'room_id' => 'cell.room_id'],
            onSuccess: fn () => $this->dispatch('close-dialog', 'cell-form'),
        );
    }

    public function clearCell(): void
    {
        if ($this->entryId === null) {
            return;
        }

        $this->callApi(fn () => $this->api()->delete('timetable-entries/'.$this->entryId), onSuccess: fn () => $this->dispatch('close-dialog', 'cell-form'));
    }

    public function publish(): void
    {
        $this->callApi(fn () => $this->api()->post('timetables/'.$this->timetableId.'/publish'), onSuccess: fn () => $this->toast('success', __('Timetable published'), __('Students and teachers can see it now.')));
    }

    public function unpublish(): void
    {
        $this->callApi(fn () => $this->api()->post('timetables/'.$this->timetableId.'/unpublish'), onSuccess: fn () => $this->toast('success', __('Back to draft')));
    }

    public function render(): View
    {
        $timetable = $this->timetable();
        $class = collect($timetable['classes'])->firstWhere('id', $this->cell['offering_section_id']);

        return view('livewire.timetable.builder', [
            'timetable' => $timetable,
            'grid' => collect($timetable['entries'])->keyBy(fn (array $entry) => $entry['day_of_week'].'-'.$entry['slot_id']),
            'clashIds' => collect($timetable['clashes'])->pluck('entry_id')->all(),
            'days' => collect($timetable['working_days'])->mapWithKeys(fn (int $day) => [$day => now()->startOfWeek()->addDays($day - 1)->translatedFormat('D')])->all(),
            'classOptions' => collect($timetable['classes'])->mapWithKeys(fn (array $c) => [$c['id'] => $c['course']['code'].' · '.$c['course']['title']])->all(),
            'teacherOptions' => collect($class['teachers'] ?? [])->mapWithKeys(fn (array $t) => [$t['id'] => $t['name'].' ('.$t['component'].')'])->all(),
            'rooms' => collect($this->api()->endpoint('rooms')->page(['status' => 'active'], ['per_page' => 200])->items)->mapWithKeys(fn (array $r) => [$r['id'] => $r['code'].($r['name'] ? ' · '.$r['name'] : '').' ('.$r['capacity'].')'])->all(),
            'can' => ['manage' => $this->can('timetable.manage') && $timetable['status'] === 'draft', 'publish' => $this->can('timetable.publish')],
        ])->title($timetable['section']['batch'].' '.$timetable['section']['name']);
    }
}
