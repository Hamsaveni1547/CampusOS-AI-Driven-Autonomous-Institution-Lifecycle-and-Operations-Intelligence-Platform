<?php

namespace App\Livewire\Admissions\Applications;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One application on the admissions desk: documents, decisions, offer and confirmation.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $applicationId = '';

    public string $remarks = '';

    public ?string $rejectDocumentId = null;

    /** @var array<string, string> */
    public array $fee = ['amount' => '', 'reference' => ''];

    public function mount(string $application): void
    {
        $this->applicationId = $application;
    }

    private function path(string $action = ''): string
    {
        return 'applications/'.$this->applicationId.($action !== '' ? '/'.$action : '');
    }

    public function reviewDocument(string $documentId, bool $verified): void
    {
        $this->callApi(
            fn () => $this->api()->post($this->path('documents/'.$documentId.'/review'), ['verified' => $verified, 'remarks' => $verified ? null : $this->remarks]),
            onSuccess: function () use ($verified): void {
                $this->reset('remarks', 'rejectDocumentId');
                $this->dispatch('close-dialog', 'reject-document');
                $this->toast('success', $verified ? __('Document verified') : __('Document rejected'));
            },
        );
    }

    public function askRejectDocument(string $documentId): void
    {
        $this->rejectDocumentId = $documentId;
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reject-document');
    }

    public function rejectDocument(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);
        $this->reviewDocument((string) $this->rejectDocumentId, false);
    }

    /**
     * Actions that need a reason: request-correction, reject. Approve takes an optional note.
     */
    public function openDecision(string $action): void
    {
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'decision-'.$action);
    }

    public function decide(string $action): void
    {
        if (! in_array($action, ['request-correction', 'reject', 'approve'], true)) {
            return;
        }

        if ($action !== 'approve') {
            $this->validate(['remarks' => 'required|string|max:1000']);
        }

        $this->callApi(fn () => $this->api()->post($this->path($action), ['remarks' => $this->remarks ?: null]), onSuccess: function () use ($action): void {
            $this->dispatch('close-dialog', 'decision-'.$action);
            $this->remarks = '';
            $this->toast('success', __('Application updated'));
        });
    }

    public function completeVerification(): void
    {
        $this->callApi(fn () => $this->api()->post($this->path('complete-verification')), onSuccess: fn () => $this->toast('success', __('Verification complete')));
    }

    public function offer(): void
    {
        $this->callApi(fn () => $this->api()->post($this->path('offer')), onSuccess: fn () => $this->toast('success', __('Seat offered'), __('The applicant has been notified.')));
    }

    public function openConfirm(): void
    {
        $application = $this->api()->endpoint('applications')->find($this->applicationId);
        $this->fee = ['amount' => (string) $application['seat']['admission_fee'], 'reference' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'confirm-admission');
    }

    public function confirm(): void
    {
        $this->validate(['fee.amount' => 'required|numeric|min:0', 'fee.reference' => 'required|string|max:100']);

        $this->callApi(
            fn () => $this->api()->post($this->path('confirm'), ['fee_amount' => (float) $this->fee['amount'], 'fee_reference' => $this->fee['reference']]),
            fieldMap: ['fee_amount' => 'fee.amount', 'fee_reference' => 'fee.reference'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'confirm-admission');
                $this->toast('success', __('Admission confirmed'));
            },
        );
    }

    public function render(): View
    {
        $application = $this->api()->endpoint('applications')->find($this->applicationId);
        $lookups = app(Lookups::class);
        $status = $application['status'];
        $documents = collect($application['documents']);
        $nothingMissing = collect($application['required_documents'])->diff($documents->pluck('document_type'))->isEmpty();
        $allVerified = $nothingMissing && $documents->every(fn (array $document) => $document['status'] === 'verified');

        return view('livewire.admissions.applications.show', [
            'application' => $application,
            'quotas' => $lookups->admissionOptions('quotas'),
            'categories' => $lookups->admissionOptions('categories'),
            'can' => [
                'verify' => $this->can('admissions.applications.verify') && in_array($status, ['submitted', 'verified'], true),
                'complete' => $this->can('admissions.applications.verify') && $status === 'submitted' && $allVerified,
                'correction' => $this->can('admissions.applications.verify') && in_array($status, ['submitted', 'verified'], true),
                'approve' => $this->can('admissions.management.approve') && $status === 'eligible' && $application['seat']['needs_approval'],
                'offer' => $this->can('admissions.seats.allocate') && ($status === 'approved' || ($status === 'eligible' && ! $application['seat']['needs_approval'])),
                'confirm' => $this->can('admissions.confirm') && $status === 'offer_accepted',
                'reject' => $this->can('admissions.applications.process') && in_array($status, ['submitted', 'correction_requested', 'verified', 'eligible', 'approved'], true),
            ],
        ])->title($application['applicant']['name']);
    }
}
