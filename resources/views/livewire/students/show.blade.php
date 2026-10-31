@php($p = $student['personal'])
@php($final = in_array($student['status'], ['graduated', 'dropped_out', 'transferred_out'], true))
<div>
    <x-ui.page-header :title="$student['name']" :description="$student['register_no'].' · '.($student['programme']['name'] ?? '')" :back="route('students.index')" :back-label="__('Students')">
        <x-slot:meta>
            <x-ui.status-badge :status="$student['status']" :label="$student['status_label']" />
            <span class="text-xs text-ink-muted">{{ __('Term :n', ['n' => $student['current_term_number']]) }}</span>
        </x-slot:meta>
        <x-slot:actions>
            @if ($can['update'])
                <x-ui.button variant="secondary" icon="layers" wire:click="openPlacement">{{ __('Placement') }}</x-ui.button>
                <x-ui.button variant="secondary" icon="pencil" wire:click="edit">{{ __('Edit') }}</x-ui.button>
            @endif
            @if ($can['status'] && ! $final)
                <x-ui.button icon="refresh-cw" wire:click="openStatus">{{ __('Change status') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($final)
        <x-ui.alert class="mb-6" tone="warning">{{ __(':status on :date. :reason', ['status' => $student['status_label'], 'date' => \App\Support\Format::date($student['left_on']), 'reason' => $student['exit_reason']]) }}</x-ui.alert>
    @endif

    @php($pendingChanges = collect($student['change_requests'])->where('status', 'pending'))
    @if ($pendingChanges->isNotEmpty())
        <x-ui.card class="mb-6" :title="__('Correction requested by the student')">
            @foreach ($pendingChanges as $request)
                <div wire:key="cr-{{ $request['id'] }}" class="flex flex-wrap items-start gap-4">
                    <div class="min-w-0 flex-1 text-sm">
                        @foreach ($request['changes'] as $field => $value)
                            <p><span class="text-ink-muted">{{ \App\Support\Format::label($field) }}:</span> {{ $p[$field] ?? ($field === 'name' ? $student['name'] : ($field === 'category' ? $student['category'] : '—')) }} → <span class="font-medium text-ink">{{ $value }}</span></p>
                        @endforeach
                        <p class="mt-1 text-xs text-ink-muted">{{ $request['reason'] }}</p>
                    </div>
                    @if ($can['approve'])
                        <x-ui.button size="sm" icon="check" wire:click="reviewChange('{{ $request['id'] }}')">{{ __('Apply') }}</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" wire:click="askReject('change', '{{ $request['id'] }}')">{{ __('Reject') }}</x-ui.button>
                    @endif
                </div>
            @endforeach
        </x-ui.card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card :title="__('Academic')">
                <x-ui.description-list :items="[
                    __('Programme') => $student['programme']['name'] ?? null,
                    __('Department') => $student['department']['name'] ?? null,
                    __('Batch') => $student['batch']['name'] ?? null,
                    __('Section') => $student['section']['name'] ?? null,
                    __('Roll number') => $student['roll_no'],
                    __('Current term') => $student['current_term_number'],
                    __('Admission quota') => $quotas[$student['admission_type']] ?? $student['admission_type'],
                    __('Category') => $student['category'] ? ($categories[$student['category']] ?? $student['category']) : null,
                    __('Admitted on') => \App\Support\Format::date($student['admitted_on']),
                    __('Application') => $student['application_no'],
                ]" />
            </x-ui.card>

            <x-ui.card :title="__('Personal')">
                <x-ui.description-list :items="[
                    __('Email') => $student['email'],
                    __('Mobile') => $student['mobile'],
                    __('Date of birth') => $p['date_of_birth'] ? \App\Support\Format::date($p['date_of_birth']) : null,
                    __('Gender') => $p['gender'] ? \App\Support\Format::label($p['gender']) : null,
                    __('Blood group') => $p['blood_group'],
                    __('Nationality') => $p['nationality'],
                    __('Religion') => $p['religion'],
                    __('Mother tongue') => $p['mother_tongue'],
                    __('ID proof') => $p['id_proof_type'] ? \App\Support\Format::label($p['id_proof_type']).' · '.$p['id_proof_number'] : null,
                    __('Address') => $p['address'],
                    __('Permanent address') => $p['permanent_address'],
                ]" />
            </x-ui.card>

            <x-ui.card :title="__('Documents')" :padding="false">
                @if ($can['update'])
                    <x-slot:actions><x-ui.button size="sm" variant="secondary" icon="upload" x-on:click="$dispatch('open-dialog', 'student-document')">{{ __('Add') }}</x-ui.button></x-slot:actions>
                @endif
                @forelse ($student['documents'] as $document)
                    <div wire:key="sd-{{ $document['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ \App\Support\Format::label($document['document_type']) }}</p>
                            <a href="{{ route('files.download', $document['file_id']) }}" class="text-xs text-primary-ink hover:underline">{{ $document['file_name'] }}</a>
                            @if ($document['remarks'])<p class="text-xs text-ink-muted">{{ $document['remarks'] }}</p>@endif
                        </div>
                        <x-ui.status-badge :status="$document['status']" size="sm" />
                        @if ($can['verify'] && $document['status'] === 'pending')
                            <x-ui.button size="sm" variant="secondary" icon="check" wire:click="approveDocument('{{ $document['id'] }}')">{{ __('Verify') }}</x-ui.button>
                            <x-ui.button size="sm" variant="ghost" wire:click="askReject('document', '{{ $document['id'] }}')">{{ __('Reject') }}</x-ui.button>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state compact icon="file-text" :title="__('No documents')" />
                @endforelse
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card :title="__('Parents / guardians')" :padding="false">
                @if ($can['update'])
                    <x-slot:actions><x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="openGuardians" :aria-label="__('Edit guardians')" /></x-slot:actions>
                @endif
                @forelse ($student['guardians'] as $guardian)
                    <div wire:key="g-{{ $guardian['id'] }}" class="border-b border-line px-5 py-3 text-sm last:border-0">
                        <p class="font-medium text-ink">{{ $guardian['name'] }} @if ($guardian['is_primary'])<x-ui.badge size="sm" tone="primary">{{ __('Primary') }}</x-ui.badge>@endif</p>
                        <p class="text-xs text-ink-muted">{{ $relations[$guardian['relation']] ?? $guardian['relation'] }}@if ($guardian['occupation']) · {{ $guardian['occupation'] }}@endif</p>
                        <p class="text-xs text-ink-muted">{{ collect([$guardian['phone'], $guardian['email']])->filter()->implode(' · ') }}</p>
                    </div>
                @empty
                    <x-ui.empty-state compact icon="users" :title="__('No guardians added')" />
                @endforelse
            </x-ui.card>

            <x-ui.card :title="__('Enrollment history')">
                <x-ui.timeline :items="collect($student['status_history'])->reverse()->map(fn ($entry) => [
                    'title' => $statuses[$entry['to']] ?? $entry['to'],
                    'description' => trim(($entry['reason'] ?? '').(isset($entry['by']) ? ' · '.__('by :name', ['name' => $entry['by']]) : ''), ' ·'),
                    'time' => $entry['effective_on'],
                    'icon' => 'circle-check',
                    'tone' => in_array($entry['to'], ['dropped_out', 'transferred_out', 'suspended', 'detained'], true) ? 'danger' : 'neutral',
                ])->values()->all()" />
            </x-ui.card>
        </div>
    </div>

    @if ($can['update'])
        <x-ui.drawer name="student-edit" size="lg" :title="__('Edit student')">
            @if ($form !== [])
                <form wire:submit="save" id="student-edit-form" class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Full name')" wire:model="form.name" required class="sm:col-span-2" />
                    <x-ui.input :label="__('Mobile')" type="tel" wire:model="form.mobile" optional />
                    <x-ui.select :label="__('Admission quota')" wire:model="form.admission_type" :options="$quotas" />
                    <x-ui.select :label="__('Category')" wire:model="form.category" :options="$categories" :placeholder="__('Choose')" optional />
                    <x-ui.date-picker :label="__('Date of birth')" wire:model="form.date_of_birth" optional />
                    <x-ui.select :label="__('Gender')" wire:model="form.gender" :options="$genders" :placeholder="__('Choose')" optional />
                    <x-ui.select :label="__('Blood group')" wire:model="form.blood_group" :options="$bloodGroups" :placeholder="__('Choose')" optional />
                    <x-ui.input :label="__('Nationality')" wire:model="form.nationality" optional />
                    <x-ui.input :label="__('Religion')" wire:model="form.religion" optional />
                    <x-ui.input :label="__('Mother tongue')" wire:model="form.mother_tongue" optional />
                    <x-ui.select :label="__('ID proof')" wire:model="form.id_proof_type" :options="$idTypes" :placeholder="__('Choose')" optional />
                    <x-ui.input :label="__('ID number')" wire:model="form.id_proof_number" optional :hint="__('Stored encrypted.')" />
                    <x-ui.textarea :label="__('Address')" wire:model="form.address" rows="2" optional class="sm:col-span-2" />
                    <x-ui.textarea :label="__('Permanent address')" wire:model="form.permanent_address" rows="2" optional class="sm:col-span-2" />
                    @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
                </form>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'student-edit')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="student-edit-form" loading="save">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.drawer>

        <x-ui.modal name="student-placement" :title="__('Batch, section and roll number')">
            <form wire:submit="savePlacement" id="student-placement-form" class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('Batch')" wire:model.live="placement.batch_id" :options="$batches" :placeholder="__('None')" />
                <x-ui.select :label="__('Section')" wire:model="placement.section_id" :options="$sections" :placeholder="__('None')" />
                <x-ui.input :label="__('Roll number')" wire:model="placement.roll_no" optional />
                <x-ui.input :label="__('Current term')" type="number" min="1" max="21" wire:model="placement.current_term_number" required />
                @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'student-placement')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="student-placement-form" loading="savePlacement">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal name="student-guardians" size="lg" :title="__('Parents / guardians')">
            <form wire:submit="saveGuardians" id="student-guardians-form" class="space-y-4">
                @foreach ($guardians as $index => $guardian)
                    <div wire:key="ge-{{ $index }}" class="grid gap-3 rounded-lg border border-line p-4 sm:grid-cols-2">
                        <x-ui.select :label="__('Relation')" wire:model="guardians.{{ $index }}.relation" :options="$relations" :placeholder="__('Choose')" required />
                        <x-ui.input :label="__('Name')" wire:model="guardians.{{ $index }}.name" required />
                        <x-ui.input :label="__('Phone')" type="tel" wire:model="guardians.{{ $index }}.phone" optional />
                        <x-ui.input :label="__('Email')" type="email" wire:model="guardians.{{ $index }}.email" optional />
                        <x-ui.input :label="__('Occupation')" wire:model="guardians.{{ $index }}.occupation" optional />
                        <div class="flex items-end justify-between gap-3">
                            <x-ui.checkbox wire:model="guardians.{{ $index }}.is_primary" :label="__('Primary contact')" />
                            <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeGuardian({{ $index }})" :aria-label="__('Remove')" />
                        </div>
                    </div>
                @endforeach
                @if (count($guardians) < 4)
                    <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="addGuardian">{{ __('Add another') }}</x-ui.button>
                @endif
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'student-guardians')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="student-guardians-form" loading="saveGuardians">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal name="student-document" :title="__('Add document')" size="sm">
            <form wire:submit="uploadDocument" id="student-document-form" class="space-y-4">
                <x-ui.input :label="__('Document')" wire:model="documentType" required :hint="__('e.g. Transfer certificate, Migration certificate')" />
                <x-ui.file-upload :label="__('File')" wire:model="documentFile" accept=".pdf,image/jpeg,image/png" :max-kb="5120" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'student-document')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="student-document-form" loading="uploadDocument">{{ __('Upload') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($can['status'])
        <x-ui.modal name="student-status" :title="__('Change enrollment status')" :description="__('Graduated, dropped out and transferred out are final and remove portal access.')">
            <form wire:submit="saveStatus" id="student-status-form" class="space-y-4">
                <x-ui.select :label="__('New status')" wire:model="statusForm.status" :options="$nextStatuses" :placeholder="__('Choose')" required />
                <x-ui.date-picker :label="__('Effective from')" wire:model="statusForm.effective_on" />
                <x-ui.textarea :label="__('Reason')" wire:model="statusForm.reason" rows="2" optional />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'student-status')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="student-status-form" loading="saveStatus">{{ __('Change status') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    <x-ui.modal name="student-reject" :title="__('Reject')" size="sm">
        <form wire:submit="reject" id="student-reject-form">
            <x-ui.input :label="__('Reason')" wire:model="remarks" required :hint="__('The student sees this.')" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'student-reject')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="student-reject-form" variant="danger" loading="reject">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
