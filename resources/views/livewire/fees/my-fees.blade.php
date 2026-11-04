@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div>
    <x-ui.page-header :title="__('My fees')" :description="__('Pay online or at the accounts counter; your receipt appears here.')">
        <x-slot:actions>
            @if ($account['totals']['balance'] > 0)
                <x-ui.button icon="credit-card" wire:click="payOnline" loading="payOnline">{{ __('Pay :a online', ['a' => $money($account['totals']['balance'])]) }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>
    @if ($formError)<x-ui.alert tone="danger" class="mb-4">{{ $formError }}</x-ui.alert>@endif

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat :label="__('Balance')" :value="$money($account['totals']['balance'])" icon="wallet" :tone="$account['totals']['overdue'] > 0 ? 'danger' : ($account['totals']['no_dues'] ? 'success' : 'warning')" :hint="$account['totals']['overdue'] > 0 ? __(':a overdue', ['a' => $money($account['totals']['overdue'])]) : null" />
        <x-ui.stat :label="__('Paid')" :value="$money($account['totals']['paid'])" icon="circle-check" tone="success" />
        <x-ui.stat :label="__('Concession')" :value="$money($account['totals']['concession'])" icon="badge-percent" tone="info" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card :title="__('Fees')" :padding="false">
            @forelse (collect($account['demands'])->where('status', '!=', 'cancelled') as $demand)
                <div wire:key="md-{{ $demand['id'] }}" class="border-b border-line px-5 py-3 text-sm last:border-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium text-ink">{{ $demand['title'] }}</span>
                        <x-ui.status-badge :status="$demand['overdue'] ? 'overdue' : $demand['status']" size="sm" />
                    </div>
                    <p class="text-xs text-ink-muted">{{ __('Due :d', ['d' => \App\Support\Format::date($demand['due_on'])]) }} · {{ collect($demand['items'])->map(fn ($i) => $i['head'].' '.$money($i['amount']))->implode(', ') }}</p>
                    <p class="mt-1 text-xs">{{ __('Total :t', ['t' => $money($demand['amount'])]) }}@if ($demand['concession'] > 0) · {{ __('concession :c', ['c' => $money($demand['concession'])]) }}@endif · {{ __('paid :p', ['p' => $money($demand['paid'])]) }} · <span class="font-semibold">{{ __('balance :b', ['b' => $money($demand['balance'])]) }}</span></p>
                </div>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No fees yet.') }}</p>
            @endforelse
        </x-ui.card>

        <x-ui.card :title="__('Receipts')" :padding="false">
            @forelse ($account['payments'] as $p)
                <a wire:key="mp-{{ $p['id'] }}" href="{{ route('fee-receipts.show', $p['id']) }}" class="flex items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0 hover:bg-surface-muted">
                    <span class="flex-1 font-mono text-ink">{{ $p['receipt_no'] }}</span>
                    <span class="text-xs text-ink-muted">{{ \App\Support\Format::date($p['paid_on']) }} · {{ $modes[$p['mode']] ?? $p['mode'] }}</span>
                    <span @class(['font-semibold', 'line-through text-ink-subtle' => $p['status'] === 'cancelled'])>{{ $money($p['amount']) }}</span>
                </a>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No payments yet.') }}</p>
            @endforelse
        </x-ui.card>
    </div>
</div>
