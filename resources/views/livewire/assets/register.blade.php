@php($money = fn ($v) => '₹'.number_format((float) $v, 0))
<div>
    <x-ui.page-header :title="__('Assets')" :description="__('Equipment register with location, warranty and repair history.')">
        @canapi('assets.manage')
            <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Add asset') }}</x-ui.button></x-slot:actions>
        @endcanapi
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-ui.stat :label="__('Assets')" :value="$summary['total']" icon="package" />
        <x-ui.stat :label="__('Value')" :value="$money($summary['value'])" icon="indian-rupee" />
        <x-ui.stat :label="__('Under repair')" :value="$summary['by_status']['under_repair'] ?? 0" icon="wrench" tone="warning" :hint="__(':n open requests', ['n' => $summary['open_requests']])" />
        <x-ui.stat :label="__('Warranty ending (60 days)')" :value="$summary['warranty_expiring']" icon="shield-alert" tone="danger" />
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <div class="w-64"><x-ui.input icon="search" wire:model.live.debounce.300ms="search" :placeholder="__('Tag, name or serial no.')" /></div>
        <div class="w-48"><x-ui.select wire:model.live="filters.category" :options="$categories" :placeholder="__('All categories')" /></div>
        <div class="w-40"><x-ui.select wire:model.live="filters.status" :options="$statuses" :placeholder="__('Any status')" /></div>
        <x-ui.checkbox wire:model.live="filters.warranty" value="1" :label="__('Warranty ending soon')" />
    </div>

    <x-ui.table loading="filters,search,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Asset') }}</x-ui.th>
            <x-ui.th>{{ __('Location') }}</x-ui.th>
            <x-ui.th>{{ __('Warranty') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $a)
            <x-ui.tr wire:key="as-{{ $a['id'] }}">
                <x-ui.td primary>
                    <button type="button" wire:click="show('{{ $a['id'] }}')" class="text-left font-medium text-ink hover:underline">{{ $a['name'] }}</button>
                    <span class="block font-mono text-xs text-ink-subtle">{{ $a['tag'] }} · {{ $a['category'] }}@if ($a['make']) · {{ $a['make'] }} {{ $a['model'] }}@endif</span>
                </x-ui.td>
                <x-ui.td :label="__('Location')">{{ $a['room']['code'] ?? '—' }}<span class="block text-xs text-ink-muted">{{ $a['department']['name'] ?? '' }}</span></x-ui.td>
                <x-ui.td :label="__('Warranty')">@if ($a['warranty_until'])<span @class(['text-danger' => $a['warranty_until'] < now()->addDays(60)->toDateString()])>{{ \App\Support\Format::date($a['warranty_until']) }}</span>@else — @endif</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$a['status']" :label="$statuses[$a['status']] ?? null" /></x-ui.td>
                <x-ui.td class="text-right">
                    @canapi('assets.manage')<x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="open('{{ $a['id'] }}')"><span class="sr-only">{{ __('Edit') }}</span></x-ui.button>@endcanapi
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="package" :title="__('No assets')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="asset-form" :title="($form['id'] ?? '') ? __('Edit asset') : __('Add asset')" size="lg">
        <form wire:submit="save" id="asset-form-el" class="grid gap-4 sm:grid-cols-3">
            <x-ui.input :label="__('Asset tag')" wire:model="form.tag" required placeholder="BIT-PC-001" />
            <x-ui.input :label="__('Name')" wire:model="form.name" required class="sm:col-span-2" />
            <x-ui.input :label="__('Category')" wire:model="form.category" required list="asset-categories" :hint="__('e.g. Computers, Projectors, Furniture')" />
            <datalist id="asset-categories">@foreach ($categories as $c)<option value="{{ $c }}"></option>@endforeach</datalist>
            <x-ui.select :label="__('Room / lab')" wire:model="form.room_id" :options="$rooms" :placeholder="__('Not placed')" optional />
            <x-ui.select :label="__('Department')" wire:model="form.department_id" :options="$departments" :placeholder="__('None')" optional />
            <x-ui.input :label="__('Make')" wire:model="form.make" optional />
            <x-ui.input :label="__('Model')" wire:model="form.model" optional />
            <x-ui.input :label="__('Serial no.')" wire:model="form.serial_no" optional />
            <x-ui.input type="date" :label="__('Purchased on')" wire:model="form.purchased_on" optional />
            <x-ui.input type="number" step="0.01" min="0" prefix="₹" :label="__('Cost')" wire:model="form.cost" optional />
            <x-ui.input :label="__('Vendor')" wire:model="form.vendor" optional />
            <x-ui.input type="date" :label="__('Warranty until')" wire:model="form.warranty_until" optional />
            <x-ui.select :label="__('Status')" wire:model="form.status" :options="$statuses" />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-3">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'asset-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="asset-form-el" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="asset-detail" :title="($detail['name'] ?? '').' · '.($detail['tag'] ?? '')" size="lg">
        @if ($detail)
            <x-ui.description-list>
                <x-ui.description-item :label="__('Make / model')">{{ trim(($detail['make'] ?? '').' '.($detail['model'] ?? '')) ?: '—' }}</x-ui.description-item>
                <x-ui.description-item :label="__('Serial no.')">{{ $detail['serial_no'] ?? '—' }}</x-ui.description-item>
                <x-ui.description-item :label="__('Purchased')">{{ $detail['purchased_on'] ? \App\Support\Format::date($detail['purchased_on']) : '—' }}@if ($detail['cost']) · {{ $money($detail['cost']) }}@endif @if ($detail['vendor']) · {{ $detail['vendor'] }}@endif</x-ui.description-item>
            </x-ui.description-list>
            <p class="mb-2 mt-4 text-sm font-medium text-ink">{{ __('Repair history') }}</p>
            @forelse ($detail['requests'] as $r)
                <div wire:key="ah-{{ $r['id'] }}" class="border-b border-line py-2 text-sm last:border-0">
                    <p class="flex justify-between"><span class="font-medium text-ink">{{ $r['title'] }}</span><x-ui.status-badge :status="$r['status']" size="sm" /></p>
                    <p class="text-xs text-ink-muted">{{ \App\Support\Format::date($r['raised_at']) }} · {{ $r['raised_by'] }}@if ($r['resolution']) · {{ $r['resolution'] }}@endif @if ($r['cost']) · {{ $money($r['cost']) }}@endif</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('No repairs recorded.') }}</p>
            @endforelse
        @endif
    </x-ui.modal>
</div>
