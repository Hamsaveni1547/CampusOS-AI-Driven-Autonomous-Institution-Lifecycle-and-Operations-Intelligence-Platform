<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Attendance percentage of every student in one class.
 */
#[Layout('layouts::app')]
class ClassAttendance extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $classId = '';

    public function mount(string $class): void
    {
        $this->classId = $class;
    }

    public function render(): View
    {
        $data = (array) $this->api()->get('attendance/classes/'.$this->classId.'/summary')->data();
        $options = (array) $this->api()->get('attendance/options', memoize: true)->data();

        return view('livewire.attendance.class-attendance', ['data' => $data, 'threshold' => $options['threshold']])
            ->title($data['class']['course']['code'].' '.__('attendance'));
    }
}
