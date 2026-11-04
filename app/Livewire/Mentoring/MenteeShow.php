<?php

namespace App\Livewire\Mentoring;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One mentee: profile, attendance, results, fees, notes, concerns and meetings.
 */
#[Layout('layouts::app')]
class MenteeShow extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $studentId = '';

    /** @var array<string, string> */
    public array $note = ['body' => '', 'visibility' => 'shared'];

    /** @var array<string, string> */
    public array $concern = ['category' => 'academic', 'description' => '', 'action_plan' => '', 'review_on' => ''];

    /** @var array<string, string> */
    public array $resolution = ['id' => '', 'outcome' => ''];

    public function mount(string $student): void
    {
        $this->studentId = $student;
    }

    public function addNote(): void
    {
        $this->validate(['note.body' => 'required|string|max:5000']);

        $this->callApi(fn () => $this->api()->post('mentees/'.$this->studentId.'/notes', $this->note), fieldMap: ['body' => 'note.body'], onSuccess: function (): void {
            $this->note = ['body' => '', 'visibility' => 'shared'];
            $this->toast('success', __('Note added'));
        });
    }

    public function openConcern(): void
    {
        $this->concern = ['category' => 'academic', 'description' => '', 'action_plan' => '', 'review_on' => now()->addWeeks(2)->toDateString()];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'add-concern');
    }

    public function addConcern(): void
    {
        $this->validate(['concern.description' => 'required|string|max:2000']);
        $data = collect($this->concern)->map(fn ($v) => $v === '' ? null : $v)->all();

        $this->callApi(fn () => $this->api()->post('mentees/'.$this->studentId.'/concerns', $data), fieldMap: ['description' => 'concern.description'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'add-concern');
            $this->toast('success', __('Concern recorded'));
        });
    }

    public function openResolve(string $id): void
    {
        $this->resolution = ['id' => $id, 'outcome' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'resolve-concern');
    }

    public function resolve(): void
    {
        $this->validate(['resolution.outcome' => 'required|string|max:2000']);

        $this->callApi(fn () => $this->api()->patch('student-concerns/'.$this->resolution['id'], ['resolve' => true, 'outcome' => $this->resolution['outcome']]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'resolve-concern');
            $this->toast('success', __('Concern resolved'));
        });
    }

    public function render(): View
    {
        $data = (array) $this->api()->withoutCache(fn () => $this->api()->get('mentees/'.$this->studentId))->data();

        return view('livewire.mentoring.mentee-show', [
            'm' => $data,
            'categories' => collect(['academic', 'attendance', 'personal', 'financial', 'health', 'other'])->mapWithKeys(fn (string $c) => [$c => __(ucfirst($c))])->all(),
        ])->title($data['name']);
    }
}
