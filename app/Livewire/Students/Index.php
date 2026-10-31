<?php

namespace App\Livewire\Students;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Student list with filters, and adding a student who did not come through admissions.
 */
#[Layout('layouts::app')]
#[Title('Students')]
class Index extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $form = [];

    protected function defaultFilters(): array
    {
        return ['programme' => '', 'department' => '', 'batch' => '', 'section' => '', 'status' => '', 'admission_type' => ''];
    }

    public function updatedFiltersBatch(): void
    {
        $this->filters['section'] = '';
    }

    public function openCreate(): void
    {
        $this->form = ['name' => '', 'email' => '', 'mobile' => '', 'programme_id' => (string) $this->filters['programme'], 'admission_type' => '', 'admitted_on' => now()->toDateString(), 'register_no' => '', 'batch_id' => '', 'section_id' => '', 'roll_no' => '', 'gender' => '', 'date_of_birth' => '', 'category' => '', 'send_invite' => true];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'student-form');
    }

    public function updatedFormProgrammeId(): void
    {
        $this->form['batch_id'] = '';
        $this->form['section_id'] = '';
    }

    public function create(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:150',
            'form.email' => 'required|email|max:255',
            'form.mobile' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
            'form.programme_id' => 'required|string',
            'form.admission_type' => 'required|string',
            'form.admitted_on' => 'required|date|before_or_equal:today',
            'form.date_of_birth' => 'nullable|date|before:-14 years',
        ]);

        $data = array_filter($this->form, fn ($value) => $value !== '' && $value !== null);
        $data['send_invite'] = (bool) $this->form['send_invite'];

        $response = $this->callApi(fn () => $this->api()->endpoint('students')->create($data));

        if ($response !== null) {
            $this->dispatch('close-dialog', 'student-form');
            $this->toast('success', __('Student added'));
            $this->redirectRoute('students.show', $response->data('id'), navigate: true);
        }
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $statuses = $lookups->studentOptions('statuses');
        $quotas = $lookups->admissionOptions('quotas');
        $programmes = $lookups->programmes(false);
        $departments = $lookups->departments(false);
        $batches = $lookups->batches($this->filters['programme'] ?: null);
        $filters = $this->activeFilters();

        return view('livewire.students.index', [
            'rows' => $this->api()->endpoint('students')->page($filters, $this->tableQuery()),
            'statuses' => $statuses + ['all' => __('All, including former students')],
            'quotas' => $quotas,
            'programmes' => $programmes,
            'departments' => $departments,
            'batches' => $batches,
            'sections' => $lookups->sections($this->filters['batch'] ?: null),
            'formBatches' => ($this->form['programme_id'] ?? '') !== '' ? $lookups->batches($this->form['programme_id']) : [],
            'formSections' => $lookups->sections($this->form['batch_id'] ?? null),
            'genders' => $this->form !== [] ? $lookups->studentOptions('genders') : [],
            'categories' => $this->form !== [] ? $lookups->admissionOptions('categories') : [],
            'canCreate' => $this->can('students.records.create'),
            'chips' => $this->filterChips([
                'programme' => ['_name' => __('Programme')] + $programmes,
                'department' => ['_name' => __('Department')] + $departments,
                'batch' => ['_name' => __('Batch')] + $batches,
                'status' => ['_name' => __('Status')] + $statuses + ['all' => __('All')],
                'admission_type' => ['_name' => __('Quota')] + $quotas,
            ]),
        ]);
    }
}
