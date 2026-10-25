<?php

namespace App\Livewire\Hr\Staff;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * One staff member: job details, personal details, status changes and qualifications.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi, WithFileUploads;

    #[Locked]
    public string $employeeId = '';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, string> */
    public array $statusForm = ['status' => 'active', 'reason' => '', 'effective_on' => ''];

    public ?string $qualificationId = null;

    /** @var array<string, string> */
    public array $qualificationForm = ['degree' => '', 'specialization' => '', 'university' => '', 'year_of_passing' => '', 'grade' => ''];

    /** @var TemporaryUploadedFile|null */
    public $certificate = null;

    public ?string $deleteQualificationId = null;

    public function mount(string $employee): void
    {
        $this->employeeId = $employee;
    }

    /** @return array<string, mixed> */
    private function employee(): array
    {
        return $this->api()->endpoint('employees')->find($this->employeeId);
    }

    public function edit(): void
    {
        $e = $this->employee();
        $p = $e['personal'] ?? [];
        $this->form = [
            'employee_code' => $e['employee_code'],
            'staff_type' => $e['staff_type'],
            'employment_type' => $e['employment_type'],
            'department_id' => (string) ($e['department']['id'] ?? ''),
            'designation_id' => (string) ($e['designation']['id'] ?? ''),
            'reports_to_id' => (string) ($e['reports_to']['id'] ?? ''),
            'joined_on' => $e['joined_on'],
            'contract_ends_on' => (string) $e['contract_ends_on'],
            'highest_qualification' => (string) $e['highest_qualification'],
            'specialization' => (string) $e['specialization'],
            'prior_experience_years' => (string) $e['prior_experience_years'],
            'gender' => (string) ($p['gender'] ?? ''),
            'date_of_birth' => (string) ($p['date_of_birth'] ?? ''),
            'address' => (string) ($p['address'] ?? ''),
            'emergency_contact_name' => (string) ($p['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => (string) ($p['emergency_contact_phone'] ?? ''),
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'staff-edit');
    }

    public function save(): void
    {
        $this->validate([
            'form.employee_code' => 'nullable|string|max:30|alpha_dash',
            'form.joined_on' => 'required|date',
            'form.contract_ends_on' => 'nullable|date|after:form.joined_on',
            'form.prior_experience_years' => 'nullable|numeric|min:0|max:60',
            'form.date_of_birth' => 'nullable|date|before:-16 years',
        ]);

        $data = array_map(fn ($value) => $value === '' ? null : $value, $this->form);
        $data['prior_experience_years'] = (float) ($data['prior_experience_years'] ?? 0);

        $this->callApi(fn () => $this->api()->endpoint('employees')->update($this->employeeId, $data), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'staff-edit');
            $this->toast('success', __('Staff details saved'));
        });
    }

    public function openStatus(): void
    {
        $this->statusForm = ['status' => $this->employee()['status'], 'reason' => '', 'effective_on' => now()->toDateString()];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'staff-status');
    }

    public function saveStatus(): void
    {
        $this->validate(['statusForm.status' => 'required|in:'.implode(',', array_keys(Index::statuses())), 'statusForm.effective_on' => 'nullable|date']);

        $this->callApi(fn () => $this->api()->endpoint('employees')->action($this->employeeId, 'status', array_map(fn ($v) => $v === '' ? null : $v, $this->statusForm)), fieldMap: ['status' => 'statusForm.status', 'reason' => 'statusForm.reason', 'effective_on' => 'statusForm.effective_on'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'staff-status');
            $this->toast('success', __('Status updated'));
        });
    }

    public function editQualification(?string $id = null): void
    {
        $this->qualificationId = $id;
        $this->certificate = null;
        $this->qualificationForm = ['degree' => '', 'specialization' => '', 'university' => '', 'year_of_passing' => '', 'grade' => ''];

        if ($id !== null) {
            $q = collect($this->employee()['qualifications'] ?? [])->firstWhere('id', $id);
            $this->qualificationForm = ['degree' => $q['degree'], 'specialization' => (string) $q['specialization'], 'university' => (string) $q['university'], 'year_of_passing' => (string) $q['year_of_passing'], 'grade' => (string) $q['grade']];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'qualification-form');
    }

    public function saveQualification(): void
    {
        $this->validate([
            'qualificationForm.degree' => 'required|string|max:100',
            'qualificationForm.year_of_passing' => 'nullable|integer|min:1950|max:'.now()->year,
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $this->callApi(function () {
            $data = array_map(fn ($v) => $v === '' ? null : $v, $this->qualificationForm);
            $data['year_of_passing'] = $data['year_of_passing'] !== null ? (int) $data['year_of_passing'] : null;

            if ($this->certificate !== null) {
                $data['certificate_file_id'] = $this->api()->upload($this->certificate, 'document')['id'];
            }

            return $this->qualificationId
                ? $this->api()->patch('employee-qualifications/'.$this->qualificationId, $data)
                : $this->api()->post('employees/'.$this->employeeId.'/qualifications', $data);
        }, fieldMap: ['degree' => 'qualificationForm.degree', 'year_of_passing' => 'qualificationForm.year_of_passing', 'certificate_file_id' => 'certificate'], onSuccess: function (): void {
            $this->certificate = null;
            $this->dispatch('close-dialog', 'qualification-form');
            $this->toast('success', __('Qualification saved'));
        });
    }

    public function askDeleteQualification(string $id): void
    {
        $this->deleteQualificationId = $id;
        $this->dispatch('open-dialog', 'delete-qualification');
    }

    public function deleteQualification(): void
    {
        $this->callApi(fn () => $this->api()->delete('employee-qualifications/'.$this->deleteQualificationId), onSuccess: function (): void {
            $this->deleteQualificationId = null;
            $this->dispatch('close-dialog', 'delete-qualification');
            $this->toast('success', __('Qualification removed'));
        });
    }

    public function render(): View
    {
        $employee = $this->employee();
        $canManage = $this->can('hr.employees.manage');
        $lookups = app(Lookups::class);
        $editing = $canManage && $this->form !== [];

        return view('livewire.hr.staff.show', [
            'employee' => $employee,
            'canManage' => $canManage,
            'departments' => $editing ? $lookups->departments() : [],
            'designations' => $editing ? $lookups->designations($this->form['staff_type'] ?? null) : [],
            'managers' => $editing ? collect($lookups->staff())->reject(fn ($s) => $s['value'] === $this->employeeId)->values()->all() : [],
        ])->title($employee['name']);
    }
}
