<?php

namespace App\Livewire\Students;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The student's own record: contact edits, documents and correction requests.
 */
#[Layout('layouts::app')]
#[Title('My student record')]
class MyRecord extends Component
{
    use InteractsWithApi, WithFileUploads;

    /** @var array<string, string> */
    public array $contact = ['address' => '', 'permanent_address' => '', 'blood_group' => '', 'mother_tongue' => ''];

    /** @var array<string, string> */
    public array $correction = [];

    public string $reason = '';

    public string $documentType = '';

    /** @var TemporaryUploadedFile|null */
    public $documentFile = null;

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    /** @return array<string, mixed> */
    private function record(): array
    {
        return $this->api()->get('me/student')->data();
    }

    public function editContact(): void
    {
        $p = $this->record()['personal'];
        $this->contact = ['address' => (string) $p['address'], 'permanent_address' => (string) $p['permanent_address'], 'blood_group' => (string) $p['blood_group'], 'mother_tongue' => (string) $p['mother_tongue']];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'my-contact');
    }

    public function saveContact(): void
    {
        $this->validate(['contact.address' => 'nullable|string|max:1000', 'contact.permanent_address' => 'nullable|string|max:1000']);

        $this->callApi(fn () => $this->api()->patch('me/student', array_map(fn ($value) => $value === '' ? null : $value, $this->contact)), fieldMap: collect(array_keys($this->contact))->mapWithKeys(fn ($key) => [$key => 'contact.'.$key])->all(), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'my-contact');
            $this->toast('success', __('Details saved'));
        });
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'image|max:2048']);

        $this->callApi(function () {
            $stored = $this->api()->upload($this->photo, 'avatar');

            return $this->api()->patch('me/student', ['photo_file_id' => $stored['id']]);
        }, fieldMap: ['photo_file_id' => 'photo'], onSuccess: fn () => $this->toast('success', __('Photo updated')));

        $this->photo = null;
    }

    public function openCorrection(): void
    {
        $this->correction = [];
        $this->reason = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'my-correction');
    }

    public function requestCorrection(): void
    {
        $changes = array_filter($this->correction, fn (string $value) => $value !== '');
        $this->validate(['reason' => 'required|string|max:500']);

        if ($changes === []) {
            $this->addError('correction', __('Fill in at least one corrected value.'));

            return;
        }

        $this->callApi(fn () => $this->api()->post('me/student/change-requests', ['changes' => $changes, 'reason' => $this->reason]), fieldMap: collect(array_keys($changes))->mapWithKeys(fn ($key) => ['changes.'.$key => 'correction.'.$key])->all(), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'my-correction');
            $this->toast('success', __('Request sent'), __('The office will review it.'));
        });
    }

    public function uploadDocument(): void
    {
        $this->validate(['documentType' => 'required|string|max:50', 'documentFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $type = str($this->documentType)->trim()->snake()->value();

        $this->callApi(function () use ($type) {
            $stored = $this->api()->upload($this->documentFile, 'document');

            return $this->api()->post('me/student/documents', ['document_type' => $type, 'file_id' => $stored['id']]);
        }, fieldMap: ['document_type' => 'documentType', 'file_id' => 'documentFile'], onSuccess: function (): void {
            $this->reset('documentType', 'documentFile');
            $this->dispatch('close-dialog', 'my-document');
            $this->toast('success', __('Document sent for verification'));
        });
    }

    public function render(): View
    {
        $record = $this->record();
        $lookups = app(Lookups::class);

        return view('livewire.students.my-record', [
            'student' => $record,
            'statuses' => $lookups->studentOptions('statuses'),
            'relations' => $lookups->studentOptions('guardian_relations'),
            'bloodGroups' => $lookups->studentOptions('blood_groups'),
            'genders' => $lookups->studentOptions('genders'),
            'idTypes' => $lookups->studentOptions('id_proof_types'),
            'categories' => $lookups->admissionOptions('categories'),
            'quotas' => $lookups->admissionOptions('quotas'),
            'pending' => collect($record['change_requests'])->firstWhere('status', 'pending'),
        ]);
    }
}
