@php($decisions = [
    'request-correction' => ['title' => __('Ask for corrections'), 'label' => __('Send to applicant'), 'hint' => __('Tell the applicant what to fix. They can edit and resubmit.'), 'required' => true],
    'reject' => ['title' => __('Reject application'), 'label' => __('Reject'), 'hint' => __('The applicant sees this reason.'), 'required' => true],
    'approve' => ['title' => __('Approve for management quota'), 'label' => __('Approve'), 'hint' => __('An offer can be made after approval.'), 'required' => false],
])
<div>
    <x-ui.page-header :title="$application['applicant']['name']" :description="($application['application_no'] ?? __('Draft')).' · '.($application['seat']['programme']['name'] ?? '')" :back="route('applications.index')" :back-label="__('Applications')">
        <x-slot:meta>
            <x-ui.status-badge :status="$application['status']" :label="$application['status_label']" />
            @if ($application['register_no'])<x-ui.badge tone="success">{{ $application['register_no'] }}</x-ui.badge>@endif
        </x-slot:meta>
        <x-slot:actions>
            @if ($can['complete'])<x-ui.button icon="circle-check" wire:click="completeVerification" loading="completeVerification">{{ __('Complete verification') }}</x-ui.button>@endif
            @if ($can['approve'])<x-ui.button icon="shield-check" wire:click="openDecision('approve')">{{ __('Approve') }}</x-ui.button>@endif
            @if ($can['offer'])<x-ui.button icon="send" wire:click="offer" wire:confirm="{{ __('Offer a seat to this applicant?') }}" loading="offer">{{ __('Offer seat') }}</x-ui.button>@endif
            @if ($can['confirm'])<x-ui.button icon="graduation-cap" wire:click="openConfirm">{{ __('Confirm admission') }}</x-ui.button>@endif
            @if ($can['correction'] || $can['reject'])
                <x-ui.dropdown>
                    <x-slot:trigger><x-ui.button variant="secondary" icon-right="chevron-down">{{ __('More') }}</x-ui.button></x-slot:trigger>
                    @if ($can['correction'])<x-ui.dropdown-item icon="pencil" wire:click="openDecision('request-correction')">{{ __('Ask for corrections') }}</x-ui.dropdown-item>@endif
                    @if ($can['reject'])<x-ui.dropdown-item icon="ban" tone="danger" wire:click="openDecision('reject')">{{ __('Reject') }}</x-ui.dropdown-item>@endif
                </x-ui.dropdown>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($application['remarks'])
        <x-ui.alert class="mb-6" :tone="in_array($application['status'], ['rejected', 'ineligible'], true) ? 'danger' : 'info'">{{ $application['remarks'] }}</x-ui.alert>
    @endif
    @if ($application['status'] === 'offered' && $application['offer_expires_at'])
        <x-ui.alert class="mb-6" tone="info">{{ __('Waiting for the applicant to accept. The offer expires :date.', ['date' => \App\Support\Format::date($application['offer_expires_at'])]) }}</x-ui.alert>
    @endif
    @if ($application['status'] === 'admitted')
        <x-ui.alert class="mb-6" tone="success">{{ __('Admitted on :date. Fee ₹:amount, receipt :ref.', ['date' => \App\Support\Format::date($application['admitted_at']), 'amount' => \App\Support\Format::number($application['fee_amount']), 'ref' => $application['fee_reference']]) }}</x-ui.alert>
    @endif

    <x-admissions.application-details :application="$application" :quotas="$quotas" :categories="$categories" :reviewable="$can['verify']" />

    @foreach ($decisions as $action => $decision)
        <x-ui.modal :name="'decision-'.$action" :title="$decision['title']" size="sm">
            <form wire:submit="decide('{{ $action }}')" id="decision-{{ $action }}-form">
                <x-ui.textarea :label="__('Remarks')" wire:model="remarks" :hint="$decision['hint']" :required="$decision['required']" :optional="! $decision['required']" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'decision-{{ $action }}')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="decision-{{ $action }}-form" loading="decide" :variant="$action === 'reject' ? 'danger' : 'primary'">{{ $decision['label'] }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach

    <x-ui.modal name="reject-document" :title="__('Reject document')" size="sm">
        <form wire:submit="rejectDocument" id="reject-document-form">
            <x-ui.input :label="__('Reason')" wire:model="remarks" required :hint="__('e.g. Blurred scan, wrong document')" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reject-document')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reject-document-form" variant="danger" loading="rejectDocument">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="confirm-admission" :title="__('Confirm admission')" :description="__('Record the admission fee. The applicant becomes a student with a register number.')">
        <form wire:submit="confirm" id="confirm-admission-form" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('Fee received')" type="number" min="0" step="0.01" wire:model="fee.amount" prefix="₹" required />
            <x-ui.input :label="__('Receipt / reference')" wire:model="fee.reference" required />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'confirm-admission')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="confirm-admission-form" loading="confirm">{{ __('Confirm admission') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
