<?php

namespace App\Livewire\Academics\Curricula;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One curriculum version: courses by term, submit and approval.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $curriculumId = '';

    /** @var array<string, mixed> */
    public array $entry = ['course_id' => '', 'term_number' => '1', 'category' => 'core', 'is_elective' => false];

    public string $remarks = '';

    public function mount(string $curriculum): void
    {
        $this->curriculumId = $curriculum;
    }

    /** @return array<string, mixed> */
    private function curriculum(): array
    {
        return $this->api()->endpoint('curricula')->find($this->curriculumId);
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    private function saveEntries(array $entries, string $message): void
    {
        $this->callApi(fn () => $this->api()->put('curricula/'.$this->curriculumId.'/entries', ['entries' => $entries]), onSuccess: function () use ($message): void {
            $this->dispatch('close-dialog', 'entry-form');
            $this->toast('success', $message);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function currentEntries(): array
    {
        return collect($this->curriculum()['entries'])->map(fn (array $entry) => [
            'course_id' => $entry['course']['id'],
            'term_number' => $entry['term_number'],
            'category' => $entry['category'],
            'is_elective' => $entry['is_elective'],
        ])->values()->all();
    }

    public function openAdd(int $termNumber = 1): void
    {
        $this->entry = ['course_id' => '', 'term_number' => (string) $termNumber, 'category' => 'core', 'is_elective' => false];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'entry-form');
    }

    public function addEntry(): void
    {
        $this->validate(['entry.course_id' => 'required|string', 'entry.term_number' => 'required|integer|min:1|max:21']);
        $entries = collect($this->currentEntries())->reject(fn (array $entry) => $entry['course_id'] === $this->entry['course_id'])->values()->all();
        $entries[] = ['course_id' => $this->entry['course_id'], 'term_number' => (int) $this->entry['term_number'], 'category' => $this->entry['category'] ?: 'core', 'is_elective' => (bool) $this->entry['is_elective']];

        $this->saveEntries($entries, __('Course added'));
    }

    public function removeEntry(string $courseId): void
    {
        $this->saveEntries(collect($this->currentEntries())->reject(fn (array $entry) => $entry['course_id'] === $courseId)->values()->all(), __('Course removed'));
    }

    public function submit(): void
    {
        $this->callApi(fn () => $this->api()->post('curricula/'.$this->curriculumId.'/submit'), onSuccess: fn () => $this->toast('success', __('Sent for approval')));
    }

    public function openDecision(bool $approve): void
    {
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', $approve ? 'curriculum-approve' : 'curriculum-return');
    }

    public function decide(bool $approve): void
    {
        if (! $approve) {
            $this->validate(['remarks' => 'required|string|max:1000']);
        }

        $this->callApi(fn () => $this->api()->post('curricula/'.$this->curriculumId.'/decide', ['approve' => $approve, 'remarks' => $this->remarks ?: null]), onSuccess: function () use ($approve): void {
            $this->dispatch('close-dialog', $approve ? 'curriculum-approve' : 'curriculum-return');
            $this->toast('success', $approve ? __('Curriculum approved') : __('Returned for changes'));
        });
    }

    public function deleteDraft(): void
    {
        $this->callApi(fn () => $this->api()->endpoint('curricula')->delete($this->curriculumId), onSuccess: function (): void {
            $this->toast('success', __('Draft deleted'));
            $this->redirectRoute('curricula.index', navigate: true);
        });
    }

    public function render(): View
    {
        $curriculum = $this->curriculum();
        $lookups = app(Lookups::class);
        $editable = $curriculum['status'] === 'draft' && $this->can('academics.curriculum.manage');

        return view('livewire.academics.curricula.show', [
            'curriculum' => $curriculum,
            'terms' => collect($curriculum['entries'])->groupBy('term_number')->sortKeys(),
            'categories' => $lookups->academicsOptions('categories'),
            'statuses' => $lookups->academicsOptions('curriculum_statuses'),
            'courses' => $editable ? $lookups->courses() : [],
            'can' => [
                'edit' => $editable,
                'submit' => $editable && $curriculum['entries'] !== [],
                'decide' => $curriculum['status'] === 'submitted' && $this->can('academics.curriculum.approve'),
                'offer' => $curriculum['status'] === 'approved' && $this->can('academics.offerings.manage'),
            ],
        ])->title(($curriculum['programme']['name'] ?? '').' '.$curriculum['regulation']);
    }
}
