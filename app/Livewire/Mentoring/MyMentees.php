<?php

namespace App\Livewire\Mentoring;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The mentor's mentees with at-risk flags, and their meetings (requests first).
 */
#[Layout('layouts::app')]
#[Title('My mentees')]
class MyMentees extends Component
{
    use InteractsWithApi;

    /** @var array<string, mixed> */
    public array $meeting = ['student_ids' => [], 'scheduled_at' => '', 'agenda' => ''];

    /** @var array<string, mixed> */
    public array $record = ['id' => '', 'status' => 'held', 'scheduled_at' => '', 'minutes' => '', 'attended_ids' => [], 'students' => []];

    public function openMeeting(): void
    {
        $this->meeting = ['student_ids' => [], 'scheduled_at' => now()->addDays(2)->setTime(15, 0)->format('Y-m-d\TH:i'), 'agenda' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'schedule-meeting');
    }

    public function schedule(): void
    {
        $this->validate(['meeting.student_ids' => 'required|array|min:1', 'meeting.scheduled_at' => 'required|date', 'meeting.agenda' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('mentoring-meetings', [...$this->meeting, 'student_ids' => array_values($this->meeting['student_ids'])]), fieldMap: ['scheduled_at' => 'meeting.scheduled_at', 'student_ids' => 'meeting.student_ids'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'schedule-meeting');
            $this->toast('success', __('Meeting scheduled'));
        });
    }

    /**
     * @param  array<string, mixed>  $meeting
     */
    public function openRecord(array $meeting, string $status): void
    {
        $this->record = ['id' => $meeting['id'], 'status' => $status, 'scheduled_at' => now()->addDay()->setTime(15, 0)->format('Y-m-d\TH:i'), 'minutes' => '', 'attended_ids' => array_column($meeting['students'], 'id'), 'students' => $meeting['students']];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'record-meeting');
    }

    public function saveRecord(): void
    {
        $r = $this->record;
        $data = ['status' => $r['status'], 'scheduled_at' => $r['status'] === 'scheduled' ? $r['scheduled_at'] : null, 'minutes' => $r['status'] === 'held' ? $r['minutes'] : null, 'attended_ids' => array_values($r['attended_ids'])];

        $this->callApi(fn () => $this->api()->patch('mentoring-meetings/'.$r['id'], $data), fieldMap: ['minutes' => 'record.minutes', 'scheduled_at' => 'record.scheduled_at'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'record-meeting');
            $this->toast('success', __('Meeting updated'));
        });
    }

    public function render(): View
    {
        $mentees = (array) $this->api()->withoutCache(fn () => $this->api()->get('me/mentees'))->data();

        return view('livewire.mentoring.my-mentees', [
            'mentees' => $mentees,
            'meetings' => (array) $this->api()->withoutCache(fn () => $this->api()->get('mentoring-meetings'))->data(),
            'menteeOptions' => collect($mentees)->mapWithKeys(fn (array $m) => [$m['id'] => $m['name']])->all(),
        ]);
    }
}
