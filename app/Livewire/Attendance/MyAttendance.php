<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The student's attendance per course, the marks of one course, and correction requests.
 */
#[Layout('layouts::app')]
#[Title('My attendance')]
class MyAttendance extends Component
{
    use InteractsWithApi;

    #[Url(as: 'course', except: '')]
    public string $class = '';

    /** @var array<string, string> */
    public array $correction = ['session_id' => '', 'requested_status' => 'on_duty', 'reason' => ''];

    public function openCorrection(string $sessionId): void
    {
        $this->correction = ['session_id' => $sessionId, 'requested_status' => 'on_duty', 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'attendance-correction');
    }

    public function requestCorrection(): void
    {
        $this->validate(['correction.requested_status' => 'required|string', 'correction.reason' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('attendance-corrections', $this->correction), fieldMap: ['requested_status' => 'correction.requested_status', 'reason' => 'correction.reason'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'attendance-correction');
            $this->toast('success', __('Request sent'));
        });
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('attendance/options', memoize: true)->data();

        return view('livewire.attendance.my-attendance', [
            'summary' => (array) $this->api()->get('me/attendance')->data(),
            'detail' => $this->class !== '' ? (array) $this->api()->withoutCache(fn () => $this->api()->get('me/attendance/'.$this->class))->data() : null,
            'labels' => collect($options['statuses'])->mapWithKeys(fn (array $s) => [$s['value'] => $s['label']])->all(),
            'threshold' => $options['threshold'],
        ]);
    }
}
