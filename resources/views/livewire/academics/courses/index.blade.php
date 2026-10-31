<div>
    <x-ui.page-header :title="__('Courses')" :description="__('The course catalogue with credits, hours and syllabus.')">
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('Add course') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Code or title…')" :active="$chips">
        <x-ui.select wire:model.live="filters.department" :options="$departments" :placeholder="__('All departments')" class="sm:w-52" :aria-label="__('Department')" />
        <x-ui.select wire:model.live="filters.type" :options="$types" :placeholder="__('Any type')" class="sm:w-44" :aria-label="__('Type')" />
        <x-ui.select wire:model.live="filters.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('Any status')" class="sm:w-36" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Course') }}</x-ui.th>
            <x-ui.th>{{ __('Department') }}</x-ui.th>
            <x-ui.th>{{ __('Type') }}</x-ui.th>
            <x-ui.th>{{ __('Credits') }}</x-ui.th>
            <x-ui.th>{{ __('L-T-P') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $course)
            <x-ui.tr wire:key="co-{{ $course['id'] }}" :href="route('courses.show', $course['id'])">
                <x-ui.td primary>
                    <a href="{{ route('courses.show', $course['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $course['title'] }}</a>
                    <p class="font-mono text-xs text-ink-muted">{{ $course['code'] }}@if ($course['regulation']) · {{ $course['regulation'] }}@endif</p>
                </x-ui.td>
                <x-ui.td :label="__('Department')">{{ $course['department']['name'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Type')">{{ $course['type_label'] }}</x-ui.td>
                <x-ui.td :label="__('Credits')">{{ $course['credits'] }}</x-ui.td>
                <x-ui.td :label="__('L-T-P')"><span class="font-mono text-xs">{{ $course['ltp'] }}</span></x-ui.td>
                <x-ui.td class="text-right">
                    @if ($canManage)
                        <x-ui.dropdown>
                            <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                            <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $course['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="askDelete('{{ $course['id'] }}')">{{ __('Delete') }}</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="book-open" :title="__('No courses found')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.drawer name="record-form" size="lg" :title="$editingId ? __('Edit course') : __('Add course')">
        <form wire:submit="save" id="record-form" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input :label="__('Code')" wire:model="form.code" required :hint="__('e.g. 21CS51')" />
                <x-ui.input :label="__('Title')" wire:model="form.title" required class="sm:col-span-2" />
                <x-ui.select :label="__('Department')" wire:model="form.department_id" :options="$departments" :placeholder="__('Choose')" required class="sm:col-span-2" />
                <x-ui.select :label="__('Type')" wire:model="form.type" :options="$types" :placeholder="__('Choose')" required />
                <x-ui.input :label="__('Credits')" type="number" step="0.5" min="0" wire:model="form.credits" required />
                <x-ui.input :label="__('Lecture hrs/week')" type="number" min="0" wire:model="form.lecture_hours" />
                <x-ui.input :label="__('Tutorial hrs/week')" type="number" min="0" wire:model="form.tutorial_hours" />
                <x-ui.input :label="__('Practical hrs/week')" type="number" min="0" wire:model="form.practical_hours" />
                <x-ui.input :label="__('Regulation')" wire:model="form.regulation" optional :hint="__('e.g. 2021')" />
                <x-ui.select :label="__('Status')" wire:model="form.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'record-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="record-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.confirm-dialog name="delete-record" :title="__('Delete this course?')" :confirm-label="__('Delete')" action="delete">
        {{ __('Only courses not used in a curriculum or offering can be deleted. Mark it inactive instead.') }}
    </x-ui.confirm-dialog>
</div>
