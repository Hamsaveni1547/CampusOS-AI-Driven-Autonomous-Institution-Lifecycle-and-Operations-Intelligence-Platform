<?php

namespace App\Livewire\Academics\Offerings;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Courses offered in a term, their sections and teachers.
 */
#[Layout('layouts::app')]
#[Title('Course offerings')]
class Index extends Component
{
    use InteractsWithApi;

    #[Url(as: 'term', except: '')]
    public string $term = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'unallocated', except: false)]
    public bool $unallocated = false;

    /** @var array<string, mixed> */
    public array $offeringForm = ['course_id' => '', 'section_ids' => [], 'is_elective' => false, 'capacity' => ''];

    public ?string $editingOfferingId = null;

    /** @var array<string, mixed> */
    public array $generateForm = ['curriculum_id' => '', 'term_number' => '', 'section_ids' => []];

    public ?string $classId = null;

    /** @var array<string, string> */
    public array $allocation = ['employee_id' => '', 'component' => 'theory', 'weekly_hours' => ''];

    public function mount(?string $generate = null): void
    {
        if ($generate !== null) {
            $this->openGenerate($generate);
        }
    }

    public function openOffering(?string $offeringId = null): void
    {
        $this->editingOfferingId = $offeringId;
        $this->offeringForm = ['course_id' => '', 'section_ids' => [], 'is_elective' => false, 'capacity' => ''];

        if ($offeringId !== null) {
            $offering = $this->api()->endpoint('course-offerings')->find($offeringId);
            $this->offeringForm = [
                'course_id' => $offering['course']['id'],
                'section_ids' => collect($offering['sections'])->pluck('section.id')->all(),
                'is_elective' => $offering['is_elective'],
                'capacity' => (string) $offering['capacity'],
            ];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'offering-form');
    }

    public function saveOffering(): void
    {
        $this->validate(['offeringForm.course_id' => 'required|string', 'offeringForm.capacity' => 'nullable|integer|min:1']);
        $data = [
            'section_ids' => array_values((array) $this->offeringForm['section_ids']),
            'is_elective' => (bool) $this->offeringForm['is_elective'],
            'capacity' => $this->offeringForm['capacity'] !== '' ? (int) $this->offeringForm['capacity'] : null,
        ];
        $offerings = $this->api()->endpoint('course-offerings');

        $this->callApi(
            fn () => $this->editingOfferingId
                ? $offerings->update($this->editingOfferingId, $data)
                : $offerings->create($data + ['course_id' => $this->offeringForm['course_id'], 'term_id' => $this->termId()]),
            fieldMap: ['course_id' => 'offeringForm.course_id', 'section_ids' => 'offeringForm.section_ids', 'capacity' => 'offeringForm.capacity'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'offering-form');
                $this->toast('success', __('Offering saved'));
            },
        );
    }

    public function removeOffering(string $offeringId): void
    {
        $this->callApi(fn () => $this->api()->endpoint('course-offerings')->delete($offeringId), onSuccess: fn () => $this->toast('success', __('Offering removed')));
    }

    public function openGenerate(?string $curriculumId = null): void
    {
        $this->generateForm = ['curriculum_id' => (string) $curriculumId, 'term_number' => '', 'section_ids' => []];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'generate-form');
    }

    public function generate(): void
    {
        $this->validate(['generateForm.curriculum_id' => 'required|string', 'generateForm.term_number' => 'required|integer|min:1|max:21', 'generateForm.section_ids' => 'required|array|min:1']);

        $response = $this->callApi(fn () => $this->api()->post('course-offerings/generate', [
            'curriculum_id' => $this->generateForm['curriculum_id'],
            'term_id' => $this->termId(),
            'term_number' => (int) $this->generateForm['term_number'],
            'section_ids' => array_values($this->generateForm['section_ids']),
        ]), fieldMap: ['section_ids' => 'generateForm.section_ids', 'curriculum_id' => 'generateForm.curriculum_id']);

        if ($response !== null) {
            $this->dispatch('close-dialog', 'generate-form');
            $this->toast('success', (string) $response->data('message'));
        }
    }

    public function openAllocate(string $classId): void
    {
        $this->classId = $classId;
        $this->allocation = ['employee_id' => '', 'component' => 'theory', 'weekly_hours' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'allocate-form');
    }

    public function allocate(): void
    {
        $this->validate(['allocation.employee_id' => 'required|string', 'allocation.component' => 'required|string', 'allocation.weekly_hours' => 'required|integer|min:1|max:30']);

        $response = $this->callApi(fn () => $this->api()->post('offering-sections/'.$this->classId.'/allocations', [
            'employee_id' => $this->allocation['employee_id'],
            'component' => $this->allocation['component'],
            'weekly_hours' => (int) $this->allocation['weekly_hours'],
        ]), fieldMap: ['employee_id' => 'allocation.employee_id', 'component' => 'allocation.component', 'weekly_hours' => 'allocation.weekly_hours']);

        if ($response !== null) {
            $this->dispatch('close-dialog', 'allocate-form');
            $warning = $response->data('warning');
            $warning ? $this->toast('warning', __('Teacher allocated'), (string) $warning) : $this->toast('success', __('Teacher allocated'));
        }
    }

    public function deallocate(string $allocationId): void
    {
        $this->callApi(fn () => $this->api()->delete('faculty-allocations/'.$allocationId), onSuccess: fn () => $this->toast('success', __('Teacher removed')));
    }

    private function termId(): string
    {
        return $this->term !== '' ? $this->term : (string) array_key_first(app(Lookups::class)->terms());
    }

    /**
     * Every section as id => "Batch · Section".
     *
     * @return array<string, string>
     */
    private function sectionOptions(): array
    {
        return collect($this->api()->endpoint('batches')->all(['status' => 'active']))
            ->flatMap(fn (array $batch) => collect($batch['sections'] ?? [])->mapWithKeys(fn (array $section) => [$section['id'] => $batch['name'].' · '.$section['name']]))
            ->all();
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $terms = $lookups->terms();
        $termId = $this->term !== '' ? $this->term : (string) array_key_first($terms);
        $manage = $this->can('academics.offerings.manage');
        $allocate = $this->can('academics.allocations.manage');

        return view('livewire.academics.offerings.index', [
            'terms' => $terms,
            'termId' => $termId,
            'offerings' => $termId !== '' ? $this->api()->endpoint('course-offerings')->all(array_filter(['term' => $termId, 'search' => trim($this->search), 'unallocated' => $this->unallocated ? 1 : null]), memoize: false) : [],
            'components' => $lookups->academicsOptions('components'),
            'courses' => $manage ? $lookups->courses() : [],
            'sections' => $manage ? $this->sectionOptions() : [],
            'curricula' => $manage ? collect($this->api()->endpoint('curricula')->all(['status' => 'approved']))->mapWithKeys(fn (array $c) => [$c['id'] => ($c['programme']['name'] ?? '').' · '.$c['regulation'].' v'.$c['version']])->all() : [],
            'teachers' => $allocate && $this->classId !== null ? $lookups->teachers() : [],
            'can' => ['manage' => $manage, 'allocate' => $allocate],
        ]);
    }
}
