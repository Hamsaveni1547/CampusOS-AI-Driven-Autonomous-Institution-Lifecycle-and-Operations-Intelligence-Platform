@php($staffTypes = \App\Livewire\Hr\Staff\Index::staffTypes())
@php($employmentTypes = \App\Livewire\Hr\Staff\Index::employmentTypes())
@php($statuses = \App\Livewire\Hr\Staff\Index::statuses())
@php($personal = $employee['personal'] ?? null)
<div>
    <x-ui.page-header :title="$employee['name']" :description="collect([$employee['designation']['name'] ?? null, $employee['department']['name'] ?? null])->filter()->implode(' · ')" :back="route('staff.index')" :back-label="__('Staff')">
        <x-slot:meta>
            <x-ui.status-badge :status="$employee['status']" />
            <span class="font-mono text-xs text-ink-muted">{{ $employee['employee_code'] }}</span>
        </x-slot:meta>
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="refresh-cw" wire:click="openStatus">{{ __('Change status') }}</x-ui.button>
                <x-ui.button icon="pencil" wire:click="edit">{{ __('Edit') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card :title="__('Job')">
                <x-ui.description-list :items="[
                    __('Staff type') => $staffTypes[$employee['staff_type']] ?? $employee['staff_type'],
                    __('Employment') => $employmentTypes[$employee['employment_type']] ?? $employee['employment_type'],
                    __('Department') => $employee['department']['name'] ?? null,
                    __('Designation') => $employee['designation']['name'] ?? null,
                    __('Reports to') => $employee['reports_to']['name'] ?? null,
                    __('Joined') => \App\Support\Format::date($employee['joined_on']),
                    __('Contract ends') => $employee['contract_ends_on'] ? \App\Support\Format::date($employee['contract_ends_on']) : null,
                    __('Left on') => $employee['left_on'] ? \App\Support\Format::date($employee['left_on']).($employee['exit_reason'] ? ' · '.$employee['exit_reason'] : '') : null,
                    __('Experience') => trans_choice(':count year|:count years', (int) round($employee['total_experience_years'] ?? 0)),
                ]" />
            </x-ui.card>

            <x-ui.card :title="__('Qualifications')" :padding="false">
                @if ($canManage)
                    <x-slot:actions><x-ui.button size="sm" icon="plus" variant="secondary" wire:click="editQualification">{{ __('Add') }}</x-ui.button></x-slot:actions>
                @endif
                @forelse ($employee['qualifications'] ?? [] as $q)
                    <div wire:key="q-{{ $q['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ $q['degree'] }}@if ($q['specialization']) · {{ $q['specialization'] }}@endif</p>
                            <p class="text-xs text-ink-muted">{{ collect([$q['university'], $q['year_of_passing'], $q['grade']])->filter()->implode(' · ') }}</p>
                        </div>
                        @if ($q['certificate_file_id'])
                            <x-ui.button size="sm" variant="ghost" icon="file-text" :href="route('files.download', $q['certificate_file_id'])">{{ __('Certificate') }}</x-ui.button>
                        @endif
                        @if ($canManage)
                            <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="editQualification('{{ $q['id'] }}')" :aria-label="__('Edit')" />
                            <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="askDeleteQualification('{{ $q['id'] }}')" :aria-label="__('Remove')" />
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state compact icon="graduation-cap" :title="__('No qualifications added')" />
                @endforelse
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card :title="__('Contact')">
                <x-ui.description-list :items="[
                    __('Email') => $employee['email'],
                    __('Mobile') => $employee['mobile'],
                    __('Account') => \App\Support\Format::label($employee['account_status'] ?? null),
                ]" />
                @canapi('identity.users.view')
                    <a href="{{ route('users.show', $employee['user_id']) }}" class="mt-4 inline-block text-sm font-medium text-primary-ink hover:underline">{{ __('Open user account') }}</a>
                @endcanapi
            </x-ui.card>
            @if ($personal)
                <x-ui.card :title="__('Personal')">
                    <x-ui.description-list :items="[
                        __('Gender') => \App\Support\Format::label($personal['gender']),
                        __('Date of birth') => $personal['date_of_birth'] ? \App\Support\Format::date($personal['date_of_birth']) : null,
                        __('Address') => $personal['address'],
                        __('Emergency contact') => trim(($personal['emergency_contact_name'] ?? '').' '.($personal['emergency_contact_phone'] ?? '')),
                    ]" />
                </x-ui.card>
            @endif
        </div>
    </div>

    @if ($canManage)
        <x-ui.drawer name="staff-edit" size="lg" :title="__('Edit staff details')">
            @if ($form !== [])
                <form wire:submit="save" id="staff-edit-form" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.select :label="__('Staff type')" wire:model.live="form.staff_type" :options="$staffTypes" />
                        <x-ui.select :label="__('Employment')" wire:model="form.employment_type" :options="$employmentTypes" />
                        <x-ui.combobox :label="__('Department')" wire:model="form.department_id" :options="$departments" optional />
                        <x-ui.combobox :label="__('Designation')" wire:model="form.designation_id" :options="$designations" optional />
                        <x-ui.combobox :label="__('Reports to')" wire:model="form.reports_to_id" :options="$managers" optional />
                        <x-ui.input :label="__('Employee code')" wire:model="form.employee_code" />
                        <x-ui.date-picker :label="__('Joined on')" wire:model="form.joined_on" />
                        <x-ui.date-picker :label="__('Contract ends')" wire:model="form.contract_ends_on" optional />
                        <x-ui.input :label="__('Highest qualification')" wire:model="form.highest_qualification" optional />
                        <x-ui.input :label="__('Specialization')" wire:model="form.specialization" optional />
                        <x-ui.input :label="__('Experience before joining (years)')" type="number" step="0.5" min="0" wire:model="form.prior_experience_years" />
                    </div>
                    <x-ui.divider />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.select :label="__('Gender')" wire:model="form.gender" :options="['female' => __('Female'), 'male' => __('Male'), 'other' => __('Other'), 'prefer_not_to_say' => __('Prefer not to say')]" :placeholder="__('Not set')" optional />
                        <x-ui.date-picker :label="__('Date of birth')" wire:model="form.date_of_birth" optional />
                        <x-ui.textarea :label="__('Address')" wire:model="form.address" rows="2" optional class="sm:col-span-2" />
                        <x-ui.input :label="__('Emergency contact')" wire:model="form.emergency_contact_name" optional />
                        <x-ui.input :label="__('Emergency phone')" wire:model="form.emergency_contact_phone" optional />
                    </div>
                    @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
                </form>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'staff-edit')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="staff-edit-form" loading="save">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.drawer>

        <x-ui.modal name="staff-status" :title="__('Change status')" :description="__('Leaving (resigned, retired, terminated) ends their roles and sign-in access.')">
            <form wire:submit="saveStatus" id="staff-status-form" class="space-y-4">
                <x-ui.select :label="__('Status')" wire:model="statusForm.status" :options="$statuses" required />
                <x-ui.date-picker :label="__('Effective from')" wire:model="statusForm.effective_on" optional />
                <x-ui.input :label="__('Reason')" wire:model="statusForm.reason" optional />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'staff-status')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="staff-status-form" loading="saveStatus">{{ __('Update') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal name="qualification-form" :title="$qualificationId ? __('Edit qualification') : __('Add qualification')">
            <form wire:submit="saveQualification" id="qualification-form" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Degree')" wire:model="qualificationForm.degree" required :hint="__('e.g. M.E., Ph.D.')" />
                    <x-ui.input :label="__('Specialization')" wire:model="qualificationForm.specialization" optional />
                    <x-ui.input :label="__('University')" wire:model="qualificationForm.university" optional class="sm:col-span-2" />
                    <x-ui.input :label="__('Year of passing')" type="number" wire:model="qualificationForm.year_of_passing" optional />
                    <x-ui.input :label="__('Grade / class')" wire:model="qualificationForm.grade" optional />
                </div>
                <x-ui.file-upload :label="__('Certificate')" wire:model="certificate" accept=".pdf,image/*" :max-kb="5120" optional />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'qualification-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="qualification-form" loading="saveQualification">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.confirm-dialog name="delete-qualification" :title="__('Remove this qualification?')" :confirm-label="__('Remove')" action="deleteQualification" />
    @endif
</div>
