<?php

namespace App\Livewire\Timetable;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Published week and one day by section, teacher or room; staff make day-to-day changes here.
 */
#[Layout('layouts::app')]
#[Title('Timetable')]
class Viewer extends Component
{
    use InteractsWithApi;

    #[Url(as: 'by', except: 'section')]
    public string $by = 'section';

    #[Url(as: 'id', except: '')]
    public string $target = '';

    #[Url(as: 'date', except: '')]
    public string $date = '';

    /** @var array<string, string> */
    public array $change = ['entry_id' => '', 'type' => 'cancelled', 'substitute_employee_id' => '', 'reason' => ''];

    /** @var array<string, string> */
    public array $extra = ['time_slot_id' => '', 'offering_section_id' => '', 'employee_id' => '', 'room_id' => '', 'reason' => ''];

    public function updatedBy(): void
    {
        $this->target = '';
    }

    private function day(): string
    {
        return $this->date !== '' ? $this->date : now()->toDateString();
    }

    public function openChange(string $entryId, string $type): void
    {
        $this->change = ['entry_id' => $entryId, 'type' => $type === 'substituted' ? 'substituted' : 'cancelled', 'substitute_employee_id' => '', 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'change-form');
    }

    public function saveChange(): void
    {
        $this->validate(['change.substitute_employee_id' => $this->change['type'] === 'substituted' ? 'required|string' : 'nullable']);

        $this->callApi(fn () => $this->api()->post('timetable-adjustments', [
            'entry_id' => $this->change['entry_id'],
            'date' => $this->day(),
            'type' => $this->change['type'],
            'substitute_employee_id' => $this->change['substitute_employee_id'] ?: null,
            'reason' => $this->change['reason'] ?: null,
        ]), fieldMap: ['substitute_employee_id' => 'change.substitute_employee_id', 'date' => 'change.reason'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'change-form');
            $this->toast('success', $this->change['type'] === 'cancelled' ? __('Class cancelled for the day') : __('Substitute assigned'));
        });
    }

    public function openExtra(): void
    {
        $this->extra = ['time_slot_id' => '', 'offering_section_id' => '', 'employee_id' => '', 'room_id' => '', 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'extra-form');
    }

    public function saveExtra(): void
    {
        $this->validate(['extra.time_slot_id' => 'required|string', 'extra.offering_section_id' => 'required|string']);
        $timetable = collect($this->api()->endpoint('timetables')->all())->firstWhere('section.id', $this->target);

        $this->callApi(fn () => $this->api()->post('timetable-adjustments/extra', array_map(fn ($v) => $v === '' ? null : $v, $this->extra) + ['timetable_id' => $timetable['id'] ?? '', 'date' => $this->day()]),
            fieldMap: ['time_slot_id' => 'extra.time_slot_id', 'offering_section_id' => 'extra.offering_section_id', 'employee_id' => 'extra.employee_id'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'extra-form');
                $this->toast('success', __('Extra class added'));
            });
    }

    public function undo(string $adjustmentId): void
    {
        $this->callApi(fn () => $this->api()->delete('timetable-adjustments/'.$adjustmentId), onSuccess: fn () => $this->toast('success', __('Regular class restored')));
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $targets = match ($this->by) {
            'teacher' => collect($lookups->teachers())->mapWithKeys(fn (array $t) => [$t['value'] => $t['label']])->all(),
            'room' => collect($this->api()->endpoint('rooms')->page(['status' => 'active'], ['per_page' => 200])->items)->mapWithKeys(fn (array $r) => [$r['id'] => $r['code'].($r['name'] ? ' · '.$r['name'] : '')])->all(),
            default => collect($this->api()->endpoint('batches')->all(['status' => 'active']))->flatMap(fn (array $b) => collect($b['sections'] ?? [])->mapWithKeys(fn (array $s) => [$s['id'] => $b['name'].' · '.$s['name']]))->all(),
        };

        $data = $this->target !== '' ? $this->api()->getMany([
            'week' => ['timetable-view/week', ['filter' => [$this->by => $this->target]]],
            'day' => ['timetable-view/day', ['filter' => [$this->by => $this->target], 'date' => $this->day()]],
        ]) : [];
        $canAdjust = $this->can('timetable.adjust');
        $timetable = $canAdjust && $this->by === 'section' && $this->target !== '' ? collect($this->api()->endpoint('timetables')->all())->firstWhere('section.id', $this->target) : null;
        $builder = $timetable !== null ? (array) $this->api()->get('timetables/'.$timetable['id'], memoize: true)->data() : null;

        return view('livewire.timetable.viewer', [
            'targets' => $targets,
            'week' => isset($data['week']) ? (array) $data['week']->data() : null,
            'periods' => isset($data['day']) ? (array) $data['day']->data('periods', []) : [],
            'dayLabel' => Carbon::parse($this->day())->translatedFormat('l, d M Y'),
            'canAdjust' => $canAdjust,
            'teachers' => $canAdjust ? collect($lookups->teachers())->mapWithKeys(fn (array $t) => [$t['value'] => $t['label']])->all() : [],
            'extraSlots' => $builder === null ? [] : collect($builder['slots'])->where('is_break', false)->mapWithKeys(fn (array $s) => [$s['id'] => $s['label'].' · '.$s['starts_at']])->all(),
            'extraClasses' => $builder === null ? [] : collect($builder['classes'])->mapWithKeys(fn (array $c) => [$c['id'] => $c['course']['code'].' · '.$c['course']['title']])->all(),
        ]);
    }
}
