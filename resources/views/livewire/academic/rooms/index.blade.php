<div>
    <x-ui.page-header :title="__('Rooms')" :description="__('Classrooms, labs and halls, used for timetables and exam seating.')">
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('Add room') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Code or name…')" :active="$chips">
        <x-ui.select wire:model.live="filters.type" :options="$types" :placeholder="__('Any type')" class="sm:w-44" :aria-label="__('Type')" />
        <x-ui.select wire:model.live="filters.department" :options="$departments" :placeholder="__('All departments')" class="sm:w-52" :aria-label="__('Department')" />
        <x-ui.input wire:model.live.debounce.500ms="filters.building" :placeholder="__('Building')" class="sm:w-36" :aria-label="__('Building')" />
        <x-ui.input type="number" wire:model.live.debounce.500ms="filters.min_capacity" :placeholder="__('Min. seats')" class="sm:w-32" :aria-label="__('Minimum seats')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,sort,gotoPage,setPerPage" :empty="$page->isEmpty()">
        <x-slot:head>
            <x-ui.th sortable="code" :sort="$sort">{{ __('Room') }}</x-ui.th>
            <x-ui.th>{{ __('Type') }}</x-ui.th>
            <x-ui.th>{{ __('Location') }}</x-ui.th>
            <x-ui.th sortable="capacity" :sort="$sort">{{ __('Seats') }}</x-ui.th>
            <x-ui.th>{{ __('Department') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($page->items as $room)
            <x-ui.tr wire:key="room-{{ $room['id'] }}">
                <x-ui.td primary>
                    <p class="font-mono font-medium text-ink">{{ $room['code'] }}</p>
                    @if ($room['name'])<p class="text-xs text-ink-muted">{{ $room['name'] }}</p>@endif
                </x-ui.td>
                <x-ui.td :label="__('Type')">{{ $types[$room['type']] ?? $room['type'] }}</x-ui.td>
                <x-ui.td :label="__('Location')">{{ collect([$room['building'], $room['floor'] ? __('Floor :f', ['f' => $room['floor']]) : null])->filter()->implode(' · ') ?: '—' }}</x-ui.td>
                <x-ui.td :label="__('Seats')">{{ $room['capacity'] }}</x-ui.td>
                <x-ui.td :label="__('Department')">{{ $room['department']['name'] ?? __('Shared') }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$room['status']" /></x-ui.td>
                <x-ui.td class="text-right">
                    @if ($canManage)
                        <x-ui.dropdown>
                            <x-slot:trigger><x-ui.button size="sm" variant="ghost" icon="ellipsis" :aria-label="__('Actions')" /></x-slot:trigger>
                            <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $room['id'] }}')">{{ __('Edit') }}</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="trash-2" tone="danger" wire:click="askDelete('{{ $room['id'] }}')">{{ __('Delete') }}</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="door-open" :title="__('No rooms found')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$page" /></x-slot:footer>
    </x-ui.table>

    <x-ui.drawer name="record-form" :title="$editingId ? __('Edit room') : __('Add room')">
        <form wire:submit="save" id="record-form" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('Code')" wire:model="form.code" required :hint="__('e.g. B-204')" />
                <x-ui.input :label="__('Name')" wire:model="form.name" optional />
                <x-ui.select :label="__('Type')" wire:model="form.type" :options="$types" required />
                <x-ui.input :label="__('Seats')" type="number" min="0" wire:model="form.capacity" required />
                <x-ui.input :label="__('Building')" wire:model="form.building" optional />
                <x-ui.input :label="__('Floor')" wire:model="form.floor" optional />
                <x-ui.select :label="__('Status')" wire:model="form.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
            </div>
            <x-ui.combobox :label="__('Department')" wire:model="form.department_id" :options="$departments" optional :hint="__('Leave empty for a shared room.')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'record-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="record-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.confirm-dialog name="delete-record" :title="__('Delete this room?')" :confirm-label="__('Delete')" action="delete">
        {{ __('Mark it inactive instead if it is used in past timetables.') }}
    </x-ui.confirm-dialog>
</div>
