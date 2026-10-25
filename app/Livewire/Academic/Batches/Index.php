<?php

namespace App\Livewire\Academic\Batches;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Batches (an admission year of a programme) and their sections.
 */
#[Layout('layouts::app')]
#[Title('Batches & sections')]
class Index extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $create = ['programme_id' => '', 'admission_year' => '', 'name' => '', 'sections' => 'A'];

    public ?string $batchId = null;

    /** @var array<string, mixed> */
    public array $batchForm = ['name' => '', 'status' => 'active'];

    public ?string $sectionId = null;

    /** @var array<string, mixed> */
    public array $sectionForm = ['name' => '', 'capacity' => '', 'class_advisor_id' => ''];

    public ?string $deleteSectionId = null;

    protected function defaultFilters(): array
    {
        return ['programme' => '', 'department' => '', 'admission_year' => '', 'status' => ''];
    }

    public function openCreate(): void
    {
        $this->create = ['programme_id' => (string) ($this->filters['programme'] ?? ''), 'admission_year' => (string) now()->year, 'name' => '', 'sections' => 'A'];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'batch-create');
    }

    public function store(): void
    {
        $this->validate([
            'create.programme_id' => 'required|string',
            'create.admission_year' => 'required|integer|min:1950|max:'.(now()->year + 1),
            'create.name' => 'nullable|string|max:150',
        ]);

        $sections = array_values(array_unique(array_filter(array_map(fn ($s) => strtoupper(trim($s)), explode(',', (string) $this->create['sections'])))));

        $this->callApi(fn () => $this->api()->endpoint('batches')->create(array_filter([
            'programme_id' => $this->create['programme_id'],
            'admission_year' => (int) $this->create['admission_year'],
            'name' => $this->create['name'] ?: null,
            'sections' => $sections,
        ], fn ($v) => $v !== null)), fieldMap: ['programme_id' => 'create.programme_id', 'admission_year' => 'create.admission_year', 'name' => 'create.name'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'batch-create');
            $this->toast('success', __('Batch created'));
        });
    }

    public function openBatch(string $id): void
    {
        $this->batchId = $id;
        $batch = $this->api()->endpoint('batches')->find($id);
        $this->batchForm = ['name' => $batch['name'], 'status' => $batch['status']];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'batch-detail');
    }

    public function saveBatch(): void
    {
        $this->validate(['batchForm.name' => 'required|string|max:150']);

        $this->callApi(fn () => $this->api()->endpoint('batches')->update((string) $this->batchId, $this->batchForm), fieldMap: ['name' => 'batchForm.name', 'status' => 'batchForm.status'], onSuccess: fn () => $this->toast('success', __('Batch updated')));
    }

    public function deleteBatch(): void
    {
        $this->callApi(fn () => $this->api()->endpoint('batches')->delete((string) $this->batchId), onSuccess: function (): void {
            $this->batchId = null;
            $this->dispatch('close-dialog', 'delete-batch');
            $this->dispatch('close-dialog', 'batch-detail');
            $this->toast('success', __('Batch deleted'));
        });
    }

    public function editSection(?string $id = null): void
    {
        $this->sectionId = $id;
        $this->sectionForm = ['name' => '', 'capacity' => '', 'class_advisor_id' => ''];

        if ($id !== null) {
            $section = collect($this->api()->endpoint('batches')->find((string) $this->batchId)['sections'] ?? [])->firstWhere('id', $id);
            $this->sectionForm = ['name' => $section['name'], 'capacity' => (string) $section['capacity'], 'class_advisor_id' => (string) ($section['class_advisor']['id'] ?? '')];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'section-form');
    }

    public function saveSection(): void
    {
        $this->validate(['sectionForm.name' => 'required|string|max:20', 'sectionForm.capacity' => 'nullable|integer|min:1|max:500']);

        $data = [
            'name' => strtoupper(trim($this->sectionForm['name'])),
            'capacity' => $this->sectionForm['capacity'] !== '' ? (int) $this->sectionForm['capacity'] : null,
            'class_advisor_id' => $this->sectionForm['class_advisor_id'] ?: null,
        ];

        $this->callApi(
            fn () => $this->sectionId
                ? $this->api()->patch('sections/'.$this->sectionId, $data)
                : $this->api()->post('batches/'.$this->batchId.'/sections', $data),
            fieldMap: ['name' => 'sectionForm.name', 'capacity' => 'sectionForm.capacity', 'class_advisor_id' => 'sectionForm.class_advisor_id'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'section-form');
                $this->toast('success', $this->sectionId ? __('Section updated') : __('Section added'));
            },
        );
    }

    public function askDeleteSection(string $id): void
    {
        $this->deleteSectionId = $id;
        $this->dispatch('open-dialog', 'delete-section');
    }

    public function deleteSection(): void
    {
        $this->callApi(fn () => $this->api()->delete('sections/'.$this->deleteSectionId), onSuccess: function (): void {
            $this->deleteSectionId = null;
            $this->dispatch('close-dialog', 'delete-section');
            $this->toast('success', __('Section removed'));
        });
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $programmes = $lookups->programmes(false);
        $departments = $lookups->departments(false);
        $batches = $this->api()->endpoint('batches')->all($this->activeFilters(null), memoize: false);
        $canManage = $this->can('institution.batches.manage');

        return view('livewire.academic.batches.index', [
            'batches' => $batches,
            'batch' => $this->batchId ? collect($batches)->firstWhere('id', $this->batchId) ?? $this->api()->endpoint('batches')->find($this->batchId) : null,
            'programmes' => $programmes,
            'departments' => $departments,
            'advisors' => $canManage && $this->batchId ? $lookups->staffUsers(['staff_type' => 'teaching']) : [],
            'canManage' => $canManage,
            'chips' => $this->filterChips([
                'programme' => ['_name' => __('Programme')] + $programmes,
                'department' => ['_name' => __('Department')] + $departments,
                'admission_year' => __('Admitted'),
                'status' => ['_name' => __('Status'), 'active' => __('Active'), 'graduated' => __('Graduated'), 'discontinued' => __('Discontinued')],
            ]),
        ]);
    }
}
