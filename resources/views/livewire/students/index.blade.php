<div>
    <x-ui.page-header :title="__('Students')" :description="__('Enrolled students. Former students are hidden unless you choose them in Status.')">
        <x-slot:actions>
            @canapi('students.records.import')
                <x-ui.button variant="secondary" icon="upload" :href="route('imports.index', ['type' => 'students'])">{{ __('Import') }}</x-ui.button>
            @endcanapi
            @if ($canCreate)<x-ui.button icon="user-plus" wire:click="openCreate">{{ __('Add student') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Name, email, register or roll number…')" :active="$chips">
        <x-ui.select wire:model.live="filters.department" :options="$departments" :placeholder="__('All departments')" class="sm:w-48" :aria-label="__('Department')" />
        <x-ui.select wire:model.live="filters.programme" :options="$programmes" :placeholder="__('All programmes')" class="sm:w-52" :aria-label="__('Programme')" />
        <x-ui.select wire:model.live="filters.batch" :options="$batches" :placeholder="__('All batches')" class="sm:w-40" :aria-label="__('Batch')" />
        @if ($sections !== [])
            <x-ui.select wire:model.live="filters.section" :options="$sections" :placeholder="__('All sections')" class="sm:w-32" :aria-label="__('Section')" />
        @endif
        <x-ui.select wire:model.live="filters.status" :options="$statuses" :placeholder="__('Enrolled')" class="sm:w-44" :aria-label="__('Status')" />
        <x-ui.select wire:model.live="filters.admission_type" :options="$quotas" :placeholder="__('Any quota')" class="sm:w-40" :aria-label="__('Quota')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,sort,gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th sortable="name" :sort="$sort">{{ __('Student') }}</x-ui.th>
            <x-ui.th sortable="register_no" :sort="$sort">{{ __('Register no.') }}</x-ui.th>
            <x-ui.th>{{ __('Programme') }}</x-ui.th>
            <x-ui.th>{{ __('Batch / section') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $student)
            <x-ui.tr wire:key="st-{{ $student['id'] }}" :href="route('students.show', $student['id'])">
                <x-ui.td primary>
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$student['name']" size="sm" />
                        <div class="min-w-0">
                            <a href="{{ route('students.show', $student['id']) }}" wire:navigate class="block truncate font-medium text-ink hover:underline">{{ $student['name'] }}</a>
                            <p class="truncate text-xs text-ink-muted">{{ $student['email'] }}</p>
                        </div>
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Register no.')"><span class="font-mono text-xs">{{ $student['register_no'] }}</span></x-ui.td>
                <x-ui.td :label="__('Programme')">{{ $student['programme']['name'] ?? '—' }}<span class="block text-xs text-ink-muted">{{ __('Term :n', ['n' => $student['current_term_number']]) }}</span></x-ui.td>
                <x-ui.td :label="__('Batch / section')">
                    {{ $student['batch']['name'] ?? '—' }}@if ($student['section']) · {{ $student['section']['name'] }}@endif
                    @if ($student['roll_no'])<span class="block text-xs text-ink-muted">{{ __('Roll :no', ['no' => $student['roll_no']]) }}</span>@endif
                </x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$student['status']" :label="$student['status_label']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="graduation-cap" :title="__('No students found')" :description="__('Try another search or clear the filters.')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.drawer name="student-form" size="lg" :title="__('Add student')" :description="__('For students who did not come through online admissions. Admitted applicants are added automatically.')">
        @if ($form !== [])
            <form wire:submit="create" id="student-form" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Full name')" wire:model="form.name" required class="sm:col-span-2" />
                    <x-ui.input :label="__('Email')" type="email" wire:model="form.email" required />
                    <x-ui.input :label="__('Mobile')" type="tel" wire:model="form.mobile" optional />
                    <x-ui.combobox :label="__('Programme')" wire:model.live="form.programme_id" :options="$programmes" required />
                    <x-ui.select :label="__('Admission quota')" wire:model="form.admission_type" :options="$quotas" :placeholder="__('Choose')" required />
                    <x-ui.date-picker :label="__('Admitted on')" wire:model="form.admitted_on" />
                    <x-ui.input :label="__('Register number')" wire:model="form.register_no" optional :hint="__('Generated when empty.')" />
                    <x-ui.select :label="__('Batch')" wire:model.live="form.batch_id" :options="$formBatches" :placeholder="__('By admission year')" optional />
                    <x-ui.select :label="__('Section')" wire:model="form.section_id" :options="$formSections" :placeholder="__('None yet')" optional />
                    <x-ui.input :label="__('Roll number')" wire:model="form.roll_no" optional />
                    <x-ui.select :label="__('Category')" wire:model="form.category" :options="$categories" :placeholder="__('Choose')" optional />
                    <x-ui.select :label="__('Gender')" wire:model="form.gender" :options="$genders" :placeholder="__('Choose')" optional />
                    <x-ui.date-picker :label="__('Date of birth')" wire:model="form.date_of_birth" optional />
                </div>
                <x-ui.switch wire:model="form.send_invite" :label="__('Send invitation email')" :description="__('The student sets a password and can use the student portal.')" />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'student-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="student-form" loading="create">{{ __('Add student') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
