@php($p = $student['personal'])
<div>
    <x-ui.page-header :title="__('My student record')" :description="$student['register_no'].' · '.($student['programme']['name'] ?? '')">
        <x-slot:meta><x-ui.status-badge :status="$student['status']" :label="$student['status_label']" /></x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="pencil" wire:click="editContact">{{ __('Edit contact details') }}</x-ui.button>
            <x-ui.button variant="secondary" icon="file-pen" wire:click="openCorrection" :disabled="$pending !== null">{{ __('Request a correction') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($pending)
        <x-ui.alert class="mb-6" tone="info" :title="__('Correction waiting for review')">
            {{ collect($pending['changes'])->map(fn ($value, $field) => \App\Support\Format::label($field).': '.$value)->implode(' · ') }}
        </x-ui.alert>
    @endif
    @php($lastRejected = collect($student['change_requests'])->firstWhere('status', 'rejected'))
    @if (! $pending && $lastRejected && $lastRejected['remarks'])
        <x-ui.alert class="mb-6" tone="warning" dismissible>{{ __('Your last correction request was not accepted: :reason', ['reason' => $lastRejected['remarks']]) }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card :title="__('Academic')">
                <x-ui.description-list :items="[
                    __('Register number') => $student['register_no'],
                    __('Programme') => $student['programme']['name'] ?? null,
                    __('Department') => $student['department']['name'] ?? null,
                    __('Batch') => $student['batch']['name'] ?? null,
                    __('Section') => $student['section']['name'] ?? null,
                    __('Roll number') => $student['roll_no'],
                    __('Current term') => $student['current_term_number'],
                    __('Admission quota') => $quotas[$student['admission_type']] ?? $student['admission_type'],
                    __('Admitted on') => \App\Support\Format::date($student['admitted_on']),
                ]" />
            </x-ui.card>

            <x-ui.card :title="__('Personal')" :description="__('To correct name, date of birth, gender, category or ID, use “Request a correction”.')">
                <x-ui.description-list :items="[
                    __('Name') => $student['name'],
                    __('Email') => $student['email'],
                    __('Mobile') => $student['mobile'],
                    __('Date of birth') => $p['date_of_birth'] ? \App\Support\Format::date($p['date_of_birth']) : null,
                    __('Gender') => $genders[$p['gender']] ?? $p['gender'],
                    __('Category') => $student['category'] ? ($categories[$student['category']] ?? $student['category']) : null,
                    __('Blood group') => $p['blood_group'],
                    __('Mother tongue') => $p['mother_tongue'],
                    __('ID proof') => $p['id_proof_type'] ? ($idTypes[$p['id_proof_type']] ?? $p['id_proof_type']).' · '.$p['id_proof_number'] : null,
                    __('Address') => $p['address'],
                    __('Permanent address') => $p['permanent_address'],
                ]" />
            </x-ui.card>

            <x-ui.card :title="__('My documents')" :padding="false">
                <x-slot:actions><x-ui.button size="sm" variant="secondary" icon="upload" x-on:click="$dispatch('open-dialog', 'my-document')">{{ __('Upload') }}</x-ui.button></x-slot:actions>
                @forelse ($student['documents'] as $document)
                    <div wire:key="md-{{ $document['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ \App\Support\Format::label($document['document_type']) }}</p>
                            <a href="{{ route('files.download', $document['file_id']) }}" class="text-xs text-primary-ink hover:underline">{{ $document['file_name'] }}</a>
                            @if ($document['status'] === 'rejected' && $document['remarks'])<p class="text-xs text-danger">{{ $document['remarks'] }}</p>@endif
                        </div>
                        <x-ui.status-badge :status="$document['status']" size="sm" />
                    </div>
                @empty
                    <x-ui.empty-state compact icon="file-text" :title="__('No documents yet')" />
                @endforelse
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card :title="__('Photo')">
                <div class="flex items-center gap-4">
                    <x-ui.avatar :name="$student['name']" :src="$student['photo_file_id'] ? route('files.download', $student['photo_file_id']) : null" size="lg" />
                    <label class="cursor-pointer text-sm font-medium text-primary-ink hover:underline">
                        <span wire:loading.remove wire:target="photo">{{ __('Change photo') }}</span>
                        <span wire:loading wire:target="photo">{{ __('Uploading…') }}</span>
                        <input type="file" class="sr-only" accept="image/*" wire:model="photo">
                    </label>
                </div>
                @error('photo')<p class="mt-2 text-xs text-danger">{{ $message }}</p>@enderror
            </x-ui.card>

            <x-ui.card :title="__('Parents / guardians')" :padding="false">
                @forelse ($student['guardians'] as $guardian)
                    <div wire:key="mg-{{ $guardian['id'] }}" class="border-b border-line px-5 py-3 text-sm last:border-0">
                        <p class="font-medium text-ink">{{ $guardian['name'] }}</p>
                        <p class="text-xs text-ink-muted">{{ $relations[$guardian['relation']] ?? $guardian['relation'] }} · {{ $guardian['phone'] }}</p>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-ink-muted">{{ __('Ask the office to add your parent or guardian.') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>

    <x-ui.modal name="my-contact" :title="__('Contact details')">
        <form wire:submit="saveContact" id="my-contact-form" class="grid gap-4 sm:grid-cols-2">
            <x-ui.textarea :label="__('Current address')" wire:model="contact.address" rows="2" class="sm:col-span-2" />
            <x-ui.textarea :label="__('Permanent address')" wire:model="contact.permanent_address" rows="2" class="sm:col-span-2" />
            <x-ui.select :label="__('Blood group')" wire:model="contact.blood_group" :options="$bloodGroups" :placeholder="__('Choose')" />
            <x-ui.input :label="__('Mother tongue')" wire:model="contact.mother_tongue" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'my-contact')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="my-contact-form" loading="saveContact">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="my-correction" :title="__('Request a correction')" :description="__('Fill only what is wrong. Attach proof under My documents if needed.')">
        <form wire:submit="requestCorrection" id="my-correction-form" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('Name')" wire:model="correction.name" optional class="sm:col-span-2" />
            <x-ui.date-picker :label="__('Date of birth')" wire:model="correction.date_of_birth" optional />
            <x-ui.select :label="__('Gender')" wire:model="correction.gender" :options="$genders" :placeholder="__('No change')" optional />
            <x-ui.select :label="__('Category')" wire:model="correction.category" :options="$categories" :placeholder="__('No change')" optional />
            <x-ui.input :label="__('Nationality')" wire:model="correction.nationality" optional />
            <x-ui.select :label="__('ID proof')" wire:model="correction.id_proof_type" :options="$idTypes" :placeholder="__('No change')" optional />
            <x-ui.input :label="__('ID number')" wire:model="correction.id_proof_number" optional />
            <x-ui.textarea :label="__('Why?')" wire:model="reason" rows="2" required class="sm:col-span-2" />
            @error('correction')<p class="text-sm text-danger sm:col-span-2">{{ $message }}</p>@enderror
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'my-correction')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="my-correction-form" loading="requestCorrection">{{ __('Send request') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="my-document" :title="__('Upload a document')" size="sm">
        <form wire:submit="uploadDocument" id="my-document-form" class="space-y-4">
            <x-ui.input :label="__('Document')" wire:model="documentType" required :hint="__('e.g. Birth certificate')" />
            <x-ui.file-upload :label="__('File')" wire:model="documentFile" accept=".pdf,image/jpeg,image/png" :max-kb="5120" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'my-document')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="my-document-form" loading="uploadDocument">{{ __('Upload') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
