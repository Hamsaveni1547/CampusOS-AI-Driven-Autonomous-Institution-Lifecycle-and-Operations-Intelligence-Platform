<?php

namespace App\Livewire\Mentoring;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Mentor allocation by section, reassignment, and the at-risk list.
 */
#[Layout('layouts::app')]
#[Title('Mentoring')]
class Allocation extends Component
{
    use InteractsWithApi;

    #[Url(except: 'allocation')]
    public string $tab = 'allocation';

    #[Url(except: '')]
    public string $batch = '';

    #[Url(except: '')]
    public string $section = '';

    /** @var array<int, string> */
    public array $mentorIds = [];

    /** @var array<string, string> */
    public array $reassign = ['student_id' => '', 'name' => '', 'mentor_id' => '', 'reason' => ''];

    public function updatedBatch(): void
    {
        $this->section = '';
    }

    public function allocate(): void
    {
        $this->validate(['section' => 'required|string', 'mentorIds' => 'required|array|min:1']);
        $this->callApi(fn () => $this->api()->post('mentoring/allocate', ['section_ids' => [$this->section], 'mentor_ids' => array_values($this->mentorIds)]), onSuccess: fn ($response) => $this->toast('success', (string) $response->data('message')));
    }

    public function openReassign(string $studentId, string $name): void
    {
        $this->reassign = ['student_id' => $studentId, 'name' => $name, 'mentor_id' => '', 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reassign-mentor');
    }

    public function saveReassign(): void
    {
        $this->validate(['reassign.mentor_id' => 'required|string']);

        $this->callApi(fn () => $this->api()->post('students/'.$this->reassign['student_id'].'/mentor', ['mentor_id' => $this->reassign['mentor_id'], 'reason' => $this->reassign['reason'] ?: null]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'reassign-mentor');
            $this->toast('success', __('Mentor changed'));
        });
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $canAllocate = $this->can('mentoring.allocate');

        return view('livewire.mentoring.allocation', [
            'batches' => $lookups->batches(),
            'sections' => $lookups->sections($this->batch ?: null),
            'teachers' => $canAllocate ? $lookups->teachers() : [],
            'students' => $this->tab === 'allocation' && $this->section !== '' ? (array) $this->api()->withoutCache(fn () => $this->api()->get('mentoring/assignments', ['filter' => ['section' => $this->section]]))->data() : [],
            'atRisk' => $this->tab === 'risk' ? (array) $this->api()->withoutCache(fn () => $this->api()->get('mentoring/at-risk', ['filter' => array_filter(['section' => $this->section])]))->data() : [],
            'canAllocate' => $canAllocate,
        ]);
    }
}
