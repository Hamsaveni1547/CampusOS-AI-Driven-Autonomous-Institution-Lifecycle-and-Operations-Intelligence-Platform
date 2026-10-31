<?php

namespace App\Livewire\Timetable;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Period templates (bell schedules).
 */
#[Layout('layouts::app')]
#[Title('Periods')]
class Periods extends Component
{
    use InteractsWithApi;

    public ?string $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function edit(?string $templateId = null): void
    {
        $this->editingId = $templateId;
        $this->form = ['name' => '', 'working_days' => ['1', '2', '3', '4', '5', '6'], 'is_default' => false, 'slots' => [['label' => 'P1', 'starts_at' => '09:00', 'ends_at' => '10:00', 'is_break' => false]]];

        if ($templateId !== null) {
            $template = collect($this->templates())->firstWhere('id', $templateId);
            $this->form = [
                'name' => $template['name'],
                'working_days' => array_map('strval', $template['working_days']),
                'is_default' => $template['is_default'],
                'slots' => collect($template['slots'])->map(fn (array $slot) => array_intersect_key($slot, array_flip(['label', 'starts_at', 'ends_at', 'is_break'])))->all(),
            ];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'template-form');
    }

    public function addSlot(bool $isBreak = false): void
    {
        $last = end($this->form['slots']) ?: ['ends_at' => '09:00'];
        $start = $last['ends_at'];
        $end = now()->setTimeFromTimeString($start)->addMinutes($isBreak ? 15 : 60)->format('H:i');
        $periods = collect($this->form['slots'])->where('is_break', false)->count();

        $this->form['slots'][] = ['label' => $isBreak ? __('Break') : 'P'.($periods + 1), 'starts_at' => $start, 'ends_at' => $end, 'is_break' => $isBreak];
    }

    public function removeSlot(int $index): void
    {
        unset($this->form['slots'][$index]);
        $this->form['slots'] = array_values($this->form['slots']);
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:100',
            'form.working_days' => 'required|array|min:1',
            'form.slots' => 'required|array|min:1',
            'form.slots.*.label' => 'required|string|max:30',
            'form.slots.*.starts_at' => 'required|date_format:H:i',
            'form.slots.*.ends_at' => 'required|date_format:H:i',
        ]);

        $data = [
            'name' => $this->form['name'],
            'working_days' => array_map('intval', array_values($this->form['working_days'])),
            'is_default' => (bool) $this->form['is_default'],
            'slots' => collect($this->form['slots'])->map(fn (array $slot) => ['label' => $slot['label'], 'starts_at' => $slot['starts_at'], 'ends_at' => $slot['ends_at'], 'is_break' => (bool) $slot['is_break']])->all(),
        ];

        $this->callApi(
            fn () => $this->editingId ? $this->api()->patch('slot-templates/'.$this->editingId, $data) : $this->api()->post('slot-templates', $data),
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'template-form');
                $this->toast('success', __('Periods saved'));
            },
        );
    }

    public function delete(string $templateId): void
    {
        $this->callApi(fn () => $this->api()->delete('slot-templates/'.$templateId), onSuccess: fn () => $this->toast('success', __('Template deleted')));
    }

    /** @return list<array<string, mixed>> */
    private function templates(): array
    {
        return array_values((array) $this->api()->get('slot-templates')->data());
    }

    public function render(): View
    {
        return view('livewire.timetable.periods', [
            'templates' => $this->templates(),
            'days' => collect(range(1, 7))->mapWithKeys(fn (int $day) => [(string) $day => now()->startOfWeek()->addDays($day - 1)->translatedFormat('D')])->all(),
            'canManage' => $this->can('timetable.manage'),
        ]);
    }
}
