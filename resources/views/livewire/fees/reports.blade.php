@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div>
    <x-ui.page-header :title="__('Fee reports')" :description="__('Who owes what, and what was collected.')" />

    <div class="mb-6 flex gap-5 border-b border-line">
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'dues')" :active="$tab === 'dues'">{{ __('Dues') }}</x-ui.tab-link>
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'collections')" :active="$tab === 'collections'">{{ __('Collections') }}</x-ui.tab-link>
    </div>

    @if ($tab === 'dues')
        <div class="mb-4 flex flex-wrap items-end gap-4">
            <div class="w-72"><x-ui.select wire:model.live="programme" :options="$programmes" :placeholder="__('All programmes')" /></div>
            <x-ui.checkbox wire:model.live="overdueOnly" :label="__('Overdue only')" />
        </div>
        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <x-ui.stat :label="__('Total due')" :value="$money($dues['total'])" icon="wallet" tone="warning" />
            <x-ui.stat :label="__('Students with dues')" :value="$dues['students']" icon="users" />
        </div>
        <x-ui.table :empty="$dues['rows'] === []">
            <x-slot:head>
                <x-ui.th>{{ __('Student') }}</x-ui.th>
                <x-ui.th>{{ __('Programme') }}</x-ui.th>
                <x-ui.th>{{ __('Oldest due') }}</x-ui.th>
                <x-ui.th>{{ __('Balance') }}</x-ui.th>
            </x-slot:head>
            @foreach ($dues['rows'] as $row)
                <x-ui.tr wire:key="du-{{ $row['student']['id'] }}">
                    <x-ui.td primary>
                        <a href="{{ route('fees.collect', ['student' => $row['student']['id']]) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $row['student']['name'] }}</a>
                        <span class="block font-mono text-xs text-ink-subtle">{{ $row['student']['register_no'] }}</span>
                    </x-ui.td>
                    <x-ui.td :label="__('Programme')">{{ $row['student']['programme'] }}</x-ui.td>
                    <x-ui.td :label="__('Oldest due')"><span @class(['text-danger' => $row['oldest_due'] < now()->toDateString()])>{{ \App\Support\Format::date($row['oldest_due']) }}</span></x-ui.td>
                    <x-ui.td :label="__('Balance')"><span class="font-semibold">{{ $money($row['balance']) }}</span></x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="circle-check" :title="__('No dues')" /></x-slot:emptyState>
        </x-ui.table>
    @else
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div class="w-44"><x-ui.input type="date" :label="__('From')" wire:model.live="from" /></div>
            <div class="w-44"><x-ui.input type="date" :label="__('To')" wire:model.live="to" /></div>
        </div>
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <x-ui.stat :label="__('Collected')" :value="$money($collections['total'])" icon="wallet" tone="success" />
            <x-ui.stat :label="__('Receipts')" :value="$collections['receipts']" icon="receipt" />
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.card :title="__('By mode')" :padding="false">
                @forelse ($collections['by_mode'] as $mode => $amount)
                    <div wire:key="bm-{{ $mode }}" class="flex justify-between border-b border-line px-5 py-2 text-sm last:border-0"><span>{{ $modes[$mode] ?? $mode }}</span><span class="font-medium">{{ $money($amount) }}</span></div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('Nothing collected in this period.') }}</p>
                @endforelse
            </x-ui.card>
            <x-ui.card :title="__('By day')" :padding="false">
                @foreach ($collections['by_day'] as $day => $amount)
                    <div wire:key="bd-{{ $day }}" class="flex justify-between border-b border-line px-5 py-2 text-sm last:border-0"><span>{{ \App\Support\Format::date($day, 'D, d M') }}</span><span class="font-medium">{{ $money($amount) }}</span></div>
                @endforeach
            </x-ui.card>
        </div>
    @endif
</div>
