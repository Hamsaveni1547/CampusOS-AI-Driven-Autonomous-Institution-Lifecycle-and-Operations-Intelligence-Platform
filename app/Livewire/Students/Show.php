<?php

namespace App\Livewire\Students;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * One student: profile, placement, guardians, documents, status and correction requests.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi, WithFileUploads;

    #[Locked]
    public string $studentId = '';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, string> */
    public array $placement = ['batch_id' => '', 'section_id' => '', 'roll_no' => '', 'current_term_number' => ''];

    /** @var array<string, string> */
    public array $statusForm = ['status' => '', 'effective_on' => '', 'reason' => ''];

    /** @var list<array<string, mixed>> */
    public array $guardians = [];

    public string $documentType = '';

    /** @var TemporaryUploadedFile|null */
    public $documentFile = null;

    public string $remarks = '';

    /** @var array{kind: string, id: string}|null document or change request being rejected */
    public ?array $rejecting = null;

    public function mount(string $student): void
    {
        $this->studentId = $student;
    }

    /** @return array<string, mixed> */
    private function student(): array
    {
        return $this->api()->endpoint('students')->find($this->studentId);
    }

    private function path(string $action = ''): string
    {
        return 'students/'.$this->studentId.($action !== '' ? '/'.$action : '');
    }

    public function edit(): void
    {
        $student = $this->student();
        $this->form = array_merge(
            ['name' => $student['name'], 'mobile' => (string) $student['mobile'], 'category' => (string) $student['category'], 'admission_type' => $student['admission_type']],
            array_map(fn ($value) => (string) $value, $student['personal']),
        );
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'student-edit');
    }

    public function save(): void
    {
        $this->validate(['form.name' => 'required|string|max:150', 'form.mobile' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'], 'form.date_of_birth' => 'nullable|date|before:-14 years']);
        $data = array_map(fn ($value) => $value === '' ? null : $value, $this->form);

        if (str_contains((string) $data['id_proof_number'], '•')) {
            unset($data['id_proof_number']);
        }

        $this->callApi(fn () => $this->api()->patch($this->path(), $data), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'student-edit');
            $this->toast('success', __('Student updated'));
        });
    }

    public function openPlacement(): void
    {
        $student = $this->student();
        $this->placement = ['batch_id' => (string) ($student['batch']['id'] ?? ''), 'section_id' => (string) ($student['section']['id'] ?? ''), 'roll_no' => (string) $student['roll_no'], 'current_term_number' => (string) $student['current_term_number']];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'student-placement');
    }

    public function updatedPlacementBatchId(): void
    {
        $this->placement['section_id'] = '';
    }

    public function savePlacement(): void
    {
        $this->validate(['placement.current_term_number' => 'required|integer|min:1|max:21', 'placement.roll_no' => 'nullable|string|max:20']);

        $this->callApi(fn () => $this->api()->post($this->path('placement'), [
            'batch_id' => $this->placement['batch_id'] ?: null,
            'section_id' => $this->placement['section_id'] ?: null,
            'roll_no' => $this->placement['roll_no'] ?: null,
            'current_term_number' => (int) $this->placement['current_term_number'],
        ]), fieldMap: collect(array_keys($this->placement))->mapWithKeys(fn ($key) => [$key => 'placement.'.$key])->all(), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'student-placement');
            $this->toast('success', __('Placement saved'));
        });
    }

    public function openStatus(): void
    {
        $this->statusForm = ['status' => '', 'effective_on' => now()->toDateString(), 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'student-status');
    }

    public function saveStatus(): void
    {
        $this->validate(['statusForm.status' => 'required|string', 'statusForm.effective_on' => 'required|date']);

        $this->callApi(fn () => $this->api()->post($this->path('status'), array_map(fn ($value) => $value === '' ? null : $value, $this->statusForm)), fieldMap: ['status' => 'statusForm.status', 'reason' => 'statusForm.reason'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'student-status');
            $this->toast('success', __('Status changed'));
        });
    }

    public function openGuardians(): void
    {
        $this->guardians = collect($this->student()['guardians'])
            ->map(fn (array $guardian) => ['relation' => $guardian['relation'], 'name' => $guardian['name'], 'phone' => (string) $guardian['phone'], 'email' => (string) $guardian['email'], 'occupation' => (string) $guardian['occupation'], 'is_primary' => $guardian['is_primary']])
            ->values()->all();

        if ($this->guardians === []) {
            $this->addGuardian();
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'student-guardians');
    }

    public function addGuardian(): void
    {
        $this->guardians[] = ['relation' => '', 'name' => '', 'phone' => '', 'email' => '', 'occupation' => '', 'is_primary' => $this->guardians === []];
    }

    public function removeGuardian(int $index): void
    {
        unset($this->guardians[$index]);
        $this->guardians = array_values($this->guardians);
    }

    public function saveGuardians(): void
    {
        $this->validate([
            'guardians.*.relation' => 'required|string',
            'guardians.*.name' => 'required|string|max:150',
            'guardians.*.phone' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
            'guardians.*.email' => 'nullable|email',
        ]);

        $payload = collect($this->guardians)->map(fn (array $guardian) => array_map(fn ($value) => $value === '' ? null : $value, $guardian))->all();

        $this->callApi(fn () => $this->api()->put($this->path('guardians'), ['guardians' => $payload]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'student-guardians');
            $this->toast('success', __('Guardians saved'));
        });
    }

    public function uploadDocument(): void
    {
        $this->validate(['documentType' => 'required|string|max:50', 'documentFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);

        $type = str($this->documentType)->trim()->snake()->value();

        $this->callApi(function () use ($type) {
            $stored = $this->api()->upload($this->documentFile, 'document');

            return $this->api()->post($this->path('documents'), ['document_type' => $type, 'file_id' => $stored['id']]);
        }, fieldMap: ['document_type' => 'documentType', 'file_id' => 'documentFile'], onSuccess: function (): void {
            $this->reset('documentType', 'documentFile');
            $this->dispatch('close-dialog', 'student-document');
            $this->toast('success', __('Document added'));
        });
    }

    public function approveDocument(string $documentId): void
    {
        $this->callApi(fn () => $this->api()->post($this->path('documents/'.$documentId.'/review'), ['approved' => true]), onSuccess: fn () => $this->toast('success', __('Document verified')));
    }

    public function reviewChange(string $requestId): void
    {
        $this->callApi(fn () => $this->api()->post('student-change-requests/'.$requestId.'/review', ['approved' => true]), onSuccess: fn () => $this->toast('success', __('Correction applied')));
    }

    public function askReject(string $kind, string $id): void
    {
        $this->rejecting = ['kind' => $kind === 'change' ? 'change' : 'document', 'id' => $id];
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'student-reject');
    }

    public function reject(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);

        if ($this->rejecting === null) {
            return;
        }

        $path = $this->rejecting['kind'] === 'change'
            ? 'student-change-requests/'.$this->rejecting['id'].'/review'
            : $this->path('documents/'.$this->rejecting['id'].'/review');

        $this->callApi(fn () => $this->api()->post($path, ['approved' => false, 'remarks' => $this->remarks]), onSuccess: function (): void {
            $this->rejecting = null;
            $this->dispatch('close-dialog', 'student-reject');
            $this->toast('success', __('Rejected'));
        });
    }

    public function render(): View
    {
        $student = $this->student();
        $lookups = app(Lookups::class);
        $statuses = $lookups->studentOptions('statuses');
        $editing = $this->can('students.records.update');

        return view('livewire.students.show', [
            'student' => $student,
            'statuses' => $statuses,
            'nextStatuses' => array_diff_key($statuses, [$student['status'] => true]),
            'quotas' => $lookups->admissionOptions('quotas'),
            'relations' => $lookups->studentOptions('guardian_relations'),
            'genders' => $editing ? $lookups->studentOptions('genders') : [],
            'bloodGroups' => $editing ? $lookups->studentOptions('blood_groups') : [],
            'idTypes' => $editing ? $lookups->studentOptions('id_proof_types') : [],
            'categories' => $lookups->admissionOptions('categories'),
            'batches' => $editing ? $lookups->batches($student['programme']['id'] ?? null) : [],
            'sections' => $editing ? $lookups->sections($this->placement['batch_id'] ?: null) : [],
            'can' => [
                'update' => $editing && $student['status'] !== 'graduated' && $student['status'] !== 'dropped_out' && $student['status'] !== 'transferred_out',
                'status' => $this->can('students.status.change'),
                'verify' => $this->can('students.documents.verify'),
                'approve' => $this->can('students.changes.approve'),
            ],
        ])->title($student['name']);
    }
}
