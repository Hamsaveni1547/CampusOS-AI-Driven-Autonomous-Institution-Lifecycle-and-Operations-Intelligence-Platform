@php($status = $application['status'])
<div>
    <x-ui.page-header :title="$application['seat']['programme']['name'] ?? __('Application')" :description="$application['cycle']['name'].' · '.($quotas[$application['seat']['quota']] ?? $application['seat']['quota'])" :back="route('my-applications.index')" :back-label="__('My applications')">
        <x-slot:meta>
            <x-ui.status-badge :status="$status" :label="$application['status_label']" />
            @if ($application['application_no'])<span class="font-mono text-xs text-ink-muted">{{ $application['application_no'] }}</span>@endif
        </x-slot:meta>
        <x-slot:actions>
            @if ($status === 'offered')
                <x-ui.button icon="circle-check" wire:click="acceptOffer" wire:confirm="{{ __('Accept this offer of admission?') }}" loading="acceptOffer">{{ __('Accept offer') }}</x-ui.button>
            @endif
            @unless (in_array($status, ['admitted', 'withdrawn', 'rejected', 'ineligible', 'expired'], true))
                <x-ui.button variant="ghost" x-on:click="$dispatch('open-dialog', 'withdraw-application')">{{ $status === 'offered' ? __('Decline') : __('Withdraw') }}</x-ui.button>
            @endunless
        </x-slot:actions>
    </x-ui.page-header>

    @if ($application['remarks'])
        <x-ui.alert class="mb-6" :tone="in_array($status, ['rejected', 'ineligible'], true) ? 'danger' : 'warning'" :title="$status === 'correction_requested' ? __('Please correct your application') : null">{{ $application['remarks'] }}</x-ui.alert>
    @endif
    @if ($status === 'offered')
        <x-ui.alert class="mb-6" tone="success" :title="__('You have been offered a seat')">{{ __('Accept before :date. Admission fee: ₹:fee.', ['date' => \App\Support\Format::date($application['offer_expires_at']), 'fee' => \App\Support\Format::number($application['seat']['admission_fee'])]) }}</x-ui.alert>
    @elseif ($status === 'offer_accepted')
        <x-ui.alert class="mb-6" tone="info">{{ __('Offer accepted. Pay the admission fee at the office to complete your admission.') }}</x-ui.alert>
    @elseif ($status === 'admitted')
        <x-ui.alert class="mb-6" tone="success" :title="__('Welcome!')">{{ __('You are admitted. Your register number is :no.', ['no' => $application['register_no']]) }}</x-ui.alert>
    @endif

    @if ($application['can_edit'])
        <form wire:submit="save" class="space-y-6">
            <x-ui.card :title="__('Personal details')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Full name (as on marks card)')" wire:model="form.personal.full_name" required class="sm:col-span-2" />
                    <x-ui.date-picker :label="__('Date of birth')" wire:model="form.personal.date_of_birth" />
                    <x-ui.select :label="__('Gender')" wire:model="form.personal.gender" :options="['female' => __('Female'), 'male' => __('Male'), 'other' => __('Other'), 'prefer_not_to_say' => __('Prefer not to say')]" :placeholder="__('Choose')" />
                    <x-ui.select :label="__('Category')" wire:model="form.category" :options="$categories" :placeholder="__('Choose')" :hint="__('Upload the category certificate if you claim a reserved category.')" />
                    <x-ui.textarea :label="__('Address')" wire:model="form.personal.address" rows="2" optional class="sm:col-span-2" />
                    <x-ui.input :label="__('Parent / guardian name')" wire:model="form.guardian.name" optional />
                    <x-ui.input :label="__('Parent / guardian phone')" type="tel" wire:model="form.guardian.phone" optional />
                </div>
            </x-ui.card>

            <x-ui.card :title="__('Academic record')">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.input :label="__('Qualifying exam %')" type="number" min="0" max="100" step="0.01" wire:model="form.academic.percentage" required :hint="$application['seat']['min_percentage'] ? __('Minimum :p%', ['p' => $application['seat']['min_percentage']]) : null" />
                    <x-ui.input :label="__('Board / university')" wire:model="form.academic.board" />
                    <x-ui.input :label="__('Year of passing')" type="number" wire:model="form.academic.year_of_passing" />
                    <x-ui.input :label="__('Entrance exam')" wire:model="form.entrance.exam" optional :hint="__('e.g. KCET, COMEDK')" />
                    <x-ui.input :label="__('Rank')" type="number" min="1" wire:model="form.entrance.rank" optional />
                    <x-ui.input :label="__('Allotment number')" wire:model="form.entrance.allotment_no" optional />
                </div>
            </x-ui.card>

            <x-ui.card :title="__('Documents')" :description="__('PDF, JPG or PNG up to 5 MB each.')" :padding="false">
                @forelse ($application['required_documents'] as $type)
                    @php($document = $uploaded->get($type))
                    <div wire:key="up-{{ $type }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ \App\Support\Format::label($type) }}</p>
                            @if ($document)
                                <a href="{{ route('files.download', $document['file_id']) }}" class="text-xs text-primary-ink hover:underline">{{ $document['file_name'] }}</a>
                                @if ($document['remarks'])<p class="text-xs text-danger">{{ $document['remarks'] }}</p>@endif
                            @endif
                            @error('uploads.'.$type)<p class="text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                        @if ($document)<x-ui.status-badge :status="$document['status']" size="sm" />@endif
                        <label class="cursor-pointer">
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-muted">
                                <x-ui.icon name="upload" />
                                <span wire:loading.remove wire:target="uploads.{{ $type }}">{{ $document ? __('Replace') : __('Upload') }}</span>
                                <span wire:loading wire:target="uploads.{{ $type }}">{{ __('Uploading…') }}</span>
                            </span>
                            <input type="file" class="sr-only" accept=".pdf,image/jpeg,image/png" wire:model="uploads.{{ $type }}">
                        </label>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-ink-muted">{{ __('No documents are needed for this cycle.') }}</p>
                @endforelse
            </x-ui.card>

            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="submit" variant="secondary" loading="save">{{ __('Save draft') }}</x-ui.button>
                <x-ui.button x-on:click="$dispatch('open-dialog', 'submit-application')">{{ __('Submit application') }}</x-ui.button>
            </div>
        </form>

        <x-ui.confirm-dialog name="submit-application" tone="primary" :title="__('Submit your application?')" :confirm-label="__('Submit')" action="submit">
            {{ __('You cannot change it after submitting, unless the admissions office asks for corrections.') }}
        </x-ui.confirm-dialog>
    @else
        <x-admissions.application-details :application="$application" :quotas="$quotas" :categories="$categories" />
    @endif

    <x-ui.confirm-dialog name="withdraw-application" :title="$status === 'offered' ? __('Decline this offer?') : __('Withdraw your application?')" :confirm-label="$status === 'offered' ? __('Decline') : __('Withdraw')" action="withdraw">
        {{ __('This cannot be undone. Any seat held for you is released.') }}
        <x-slot:fields><div class="mt-4"><x-ui.input :label="__('Reason')" wire:model="reason" optional /></div></x-slot:fields>
    </x-ui.confirm-dialog>
</div>
