@php($categories = ['teaching' => __('Teaching'), 'non_teaching' => __('Non-teaching')])
<div>
    <x-ui.page-header :title="__('Designations')" :description="__('Job titles such as Professor or Lab Assistant. Lower rank means more senior.')">
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('Add designation') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters :active="$chips">
        <x-ui.select wire:model.live="filters.category" :options="$categories" :placeholder="__('Any category')" class="sm:w-44" :aria-label="__('Category')" />
        <x-ui.select wire:model.live="filters.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="filters" :empty="$designations === []">
        <x-slot:head>
            <x-ui.th>{{ __('Designation') }}</x-ui.th>
            <x-ui.th>{{ __('Category') }}</x-ui.th>
            <x-ui.th>{{ __('Rank') }}</x-ui.th>
            <x-ui.th>{{ __('Staff') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($designations as $designation)
            <x-ui.tr wire:key="des-{{ $designation['id'] }}">
                <x-ui.td primary><span class="font-medium text-ink">{{ $designation['name'] }}</span></x-ui.td>
                <x-ui.td :label="__('Category')">{{ $categories[$designation['category']] ?? $designation['category'] }}</x-ui.td>
                <x-ui.td :label="__('Rank')">{{ $designation['rank'] }}</x-ui.td>
                <x-ui.td :label="__('Staff')"><a class="hover:underline" href="{{ route('staff.index', ['f' => ['designation' => $designation['id']]]) }}">{{ $designation['employees_count'] ?? 0 }}</a></x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$designation['status']" /></x-ui.td>
                <x-ui.td class="text-right">
                    @if ($canManage)
                        <x-ui.dropdown>
                            <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                            <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $designation['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="askDelete('{{ $designation['id'] }}')">{{ __('Delete') }}</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="award" :title="__('No designations yet')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.modal name="record-form" :title="$editingId ? __('Edit designation') : __('Add designation')">
        <form wire:submit="save" id="record-form" class="space-y-4">
            <x-ui.input :label="__('Name')" wire:model="form.name" required />
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.select :label="__('Category')" wire:model="form.category" :options="$categories" required />
                <x-ui.input :label="__('Rank')" type="number" min="1" max="1000" wire:model="form.rank" optional />
                <x-ui.select :label="__('Status')" wire:model="form.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'record-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="record-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-dialog name="delete-record" :title="__('Delete this designation?')" :confirm-label="__('Delete')" action="delete">
        {{ __('Only designations nobody holds can be deleted.') }}
    </x-ui.confirm-dialog>
</div>
