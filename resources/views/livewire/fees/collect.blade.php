@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div>
    <x-ui.page-header :title="__('Collect fees')" :description="__('Find a student, take a payment and issue a receipt.')" />

    <div class="relative mb-6 max-w-md">
        <x-ui.input icon="search" wire:model.live.debounce.300ms="search" :placeholder="__('Name or register number')" />
        @if ($matches !== [])
            <div class="absolute z-10 mt-1 w-full rounded-md border border-line bg-surface shadow-lg">
                @foreach ($matches as $match)
                    <button type="button" wire:key="m-{{ $match['id'] }}" wire:click="choose('{{ $match['id'] }}')" class="block w-full px-4 py-2 text-left text-sm hover:bg-surface-muted">
                        <span class="font-medium text-ink">{{ $match['name'] }}</span> <span class="font-mono text-xs text-ink-muted">{{ $match['register_no'] }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($account === null)
        <x-ui.empty-state icon="wallet" :title="__('No student chosen')" :description="__('Search above to open a student’s fee account.')" />
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-lg font-semibold text-ink">{{ $account['student']['name'] }}</p>
                <p class="text-sm text-ink-muted"><span class="font-mono">{{ $account['student']['register_no'] }}</span> · {{ $account['student']['programme'] }}</p>
            </div>
            @canapi('finance.demands.manage')<x-ui.button variant="secondary" icon="plus" wire:click="openCharge">{{ __('Add a charge') }}</x-ui.button>@endcanapi
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-4">
            <x-ui.stat :label="__('Billed')" :value="$money($account['totals']['billed'])" icon="file-text" />
            <x-ui.stat :label="__('Concession')" :value="$money($account['totals']['concession'])" icon="badge-percent" tone="info" />
            <x-ui.stat :label="__('Paid')" :value="$money($account['totals']['paid'])" icon="circle-check" tone="success" />
            <x-ui.stat :label="__('Balance')" :value="$money($account['totals']['balance'])" icon="wallet" :tone="$account['totals']['overdue'] > 0 ? 'danger' : 'warning'" :hint="$account['totals']['overdue'] > 0 ? __(':a overdue', ['a' => $money($account['totals']['overdue'])]) : ($account['totals']['no_dues'] ? __('No dues') : null)" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card :title="__('Demands')" :padding="false">
                    @forelse ($account['demands'] as $demand)
                        <div wire:key="d-{{ $demand['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-ink">{{ $demand['title'] }}</p>
                                <p class="text-xs text-ink-muted">{{ __('Due :d', ['d' => \App\Support\Format::date($demand['due_on'])]) }} · {{ collect($demand['items'])->map(fn ($i) => $i['head'].' '.$money($i['amount']))->implode(', ') }}@if ($demand['concession'] > 0) · {{ __('concession :a', ['a' => $money($demand['concession'])]) }}@endif</p>
                                @if ($demand['cancel_reason'])<p class="text-xs text-danger">{{ $demand['cancel_reason'] }}</p>@endif
                            </div>
                            <span class="text-right"><span class="font-semibold text-ink">{{ $money($demand['balance']) }}</span><span class="block text-xs text-ink-muted">{{ __('of :a', ['a' => $money($demand['amount'])]) }}</span></span>
                            <x-ui.status-badge :status="$demand['overdue'] ? 'overdue' : $demand['status']" size="sm" />
                            @canapi('finance.demands.manage')
                                @if (in_array($demand['status'], ['open', 'partly_paid'], true))
                                    <x-ui.button size="sm" variant="ghost" wire:click="openConcession('{{ $demand['id'] }}', @js($demand['title']))">{{ __('Concession') }}</x-ui.button>
                                @endif
                                @if ($demand['status'] === 'open' && $demand['paid'] == 0)
                                    <x-ui.button size="sm" variant="ghost" icon="x" wire:click="askCancel('demand', '{{ $demand['id'] }}', @js($demand['title']))"><span class="sr-only">{{ __('Cancel') }}</span></x-ui.button>
                                @endif
                            @endcanapi
                        </div>
                    @empty
                        <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No fees raised yet.') }}</p>
                    @endforelse
                </x-ui.card>

                <x-ui.card :title="__('Receipts')" :padding="false">
                    @forelse ($account['payments'] as $p)
                        <div wire:key="p-{{ $p['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                            <a href="{{ route('fee-receipts.show', $p['id']) }}" class="min-w-0 flex-1 font-mono text-ink hover:underline">{{ $p['receipt_no'] }}</a>
                            <span class="text-xs text-ink-muted">{{ \App\Support\Format::date($p['paid_on']) }} · {{ $modes[$p['mode']] ?? $p['mode'] }}@if ($p['reference']) · {{ $p['reference'] }}@endif</span>
                            <span @class(['font-semibold', 'text-ink' => $p['status'] === 'valid', 'text-ink-subtle line-through' => $p['status'] === 'cancelled'])>{{ $money($p['amount']) }}</span>
                            @if ($p['status'] === 'cancelled')
                                <x-ui.status-badge status="cancelled" size="sm" />
                            @else
                                @canapi('finance.payments.cancel')
                                    <x-ui.button size="sm" variant="ghost" icon="x" wire:click="askCancel('payment', '{{ $p['id'] }}', @js($p['receipt_no']))"><span class="sr-only">{{ __('Cancel receipt') }}</span></x-ui.button>
                                @endcanapi
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No payments yet.') }}</p>
                    @endforelse
                </x-ui.card>
            </div>

            @canapi('finance.payments.collect')
                <x-ui.card :title="__('Take a payment')">
                    @if ($account['totals']['balance'] <= 0)
                        <p class="text-sm text-ink-muted">{{ __('Nothing is due.') }}</p>
                    @else
                        <form wire:submit="collect" class="space-y-4">
                            <x-ui.input type="number" step="0.01" min="1" :label="__('Amount')" prefix="₹" wire:model="payment.amount" required :hint="__('Applied to the earliest dues first.')" />
                            <x-ui.select :label="__('Mode')" wire:model.live="payment.mode" :options="$modes" required />
                            @if ($payment['mode'] !== 'cash')
                                <x-ui.input :label="__('Cheque / DD / transaction no.')" wire:model="payment.reference" required />
                            @endif
                            <x-ui.input type="date" :label="__('Paid on')" wire:model="payment.paid_on" required />
                            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
                            <x-ui.button type="submit" icon="receipt" class="w-full" loading="collect">{{ __('Issue receipt') }}</x-ui.button>
                        </form>
                    @endif
                </x-ui.card>
            @endcanapi
        </div>
    @endif

    <x-ui.modal name="add-charge" :title="__('Add a charge')" size="sm">
        <form wire:submit="addCharge" id="add-charge-form" class="space-y-4">
            <x-ui.input :label="__('Title')" wire:model="charge.title" required :hint="__('e.g. Library fine, Duplicate ID card')" />
            <x-ui.select :label="__('Fee head')" wire:model="charge.fee_head_id" :options="$heads" :placeholder="__('Choose…')" required />
            <x-ui.input type="number" step="0.01" min="1" :label="__('Amount')" prefix="₹" wire:model="charge.amount" required />
            <x-ui.input type="date" :label="__('Due on')" wire:model="charge.due_on" required />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'add-charge')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="add-charge-form" loading="addCharge">{{ __('Add') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="concession" :title="__('Ask for a concession')" size="sm">
        <form wire:submit="requestConcession" id="concession-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ $concession['title'] }}</p>
            <x-ui.input type="number" step="0.01" min="1" :label="__('Amount')" prefix="₹" wire:model="concession.amount" required />
            <x-ui.textarea :label="__('Reason')" wire:model="concession.reason" rows="2" required :hint="__('e.g. merit scholarship, sibling concession')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'concession')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="concession-form" loading="requestConcession">{{ __('Send for approval') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="cancel-fee" :title="$cancel['type'] === 'payment' ? __('Cancel receipt') : __('Cancel demand')" size="sm">
        <form wire:submit="confirmCancel" id="cancel-fee-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ $cancel['label'] }}@if ($cancel['type'] === 'payment') — {{ __('the payment will be reversed; the receipt number is kept.') }}@endif</p>
            <x-ui.input :label="__('Reason')" wire:model="cancel.reason" required />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'cancel-fee')">{{ __('Back') }}</x-ui.button>
            <x-ui.button type="submit" form="cancel-fee-form" variant="danger" loading="confirmCancel">{{ __('Cancel it') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
