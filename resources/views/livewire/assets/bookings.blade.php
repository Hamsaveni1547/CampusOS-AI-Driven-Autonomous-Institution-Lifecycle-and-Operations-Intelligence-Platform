<div>
    <x-ui.page-header :title="__('Room bookings')" :description="__('Book a lab or room for extra sessions, project work or events. Timetabled classes come first.')">
        @canapi('assets.rooms.book')
            <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Book a room') }}</x-ui.button></x-slot:actions>
        @endcanapi
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-64"><x-ui.select wire:model.live="filters.room" :options="$rooms" :placeholder="__('All rooms')" /></div>
        <x-ui.checkbox wire:model.live="filters.mine" value="1" :label="__('My bookings')" />
    </div>

    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('When') }}</x-ui.th>
            <x-ui.th>{{ __('Room') }}</x-ui.th>
            <x-ui.th>{{ __('Purpose') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $b)
            <x-ui.tr wire:key="rb-{{ $b['id'] }}">
                <x-ui.td primary>{{ \App\Support\Format::date($b['date'], 'D, d M') }}<span class="block font-mono text-xs text-ink-muted">{{ $b['starts_at'] }}–{{ $b['ends_at'] }}</span></x-ui.td>
                <x-ui.td :label="__('Room')">{{ $b['room']['code'] }}<span class="block text-xs text-ink-muted">{{ $b['room']['name'] }}</span></x-ui.td>
                <x-ui.td :label="__('Purpose')">{{ $b['purpose'] }}<span class="block text-xs text-ink-subtle">{{ $b['booked_by'] }}</span></x-ui.td>
                <x-ui.td class="text-right">@if ($b['can_cancel'])<x-ui.button size="sm" variant="ghost" wire:click="cancel('{{ $b['id'] }}')" wire:confirm="{{ __('Cancel this booking?') }}">{{ __('Cancel') }}</x-ui.button>@endif</x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="calendar" :title="__('No upcoming bookings')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="book-room" :title="__('Book a room')" size="sm">
        <form wire:submit="book" id="book-room-form" class="space-y-4">
            <x-ui.select :label="__('Room / lab')" wire:model="form.room_id" :options="$rooms" :placeholder="__('Choose…')" required />
            <x-ui.input type="date" :label="__('Date')" wire:model="form.date" required />
            <div class="grid grid-cols-2 gap-3">
                <x-ui.input type="time" :label="__('From')" wire:model="form.starts_at" required />
                <x-ui.input type="time" :label="__('To')" wire:model="form.ends_at" required />
            </div>
            <x-ui.input :label="__('Purpose')" wire:model="form.purpose" required />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'book-room')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="book-room-form" loading="book">{{ __('Book') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
