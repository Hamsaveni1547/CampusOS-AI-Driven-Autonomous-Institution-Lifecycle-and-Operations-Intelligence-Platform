@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div class="mx-auto max-w-lg">
    <x-ui.page-header :title="__('Pay fees online')" :back="route('my-fees.show')" :back-label="__('My fees')" />

    <x-ui.card>
        <div class="mb-5 flex items-center justify-between border-b border-line pb-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-subtle">{{ __('CampusPay (demo gateway)') }}</p>
                <p class="text-2xl font-semibold text-ink">{{ $money($payment['amount']) }}</p>
            </div>
            <x-ui.status-badge :status="$payment['status']" />
        </div>

        @if ($payment['status'] === 'pending')
            <p class="mb-3 text-sm font-medium text-ink">{{ __('Pay with') }}</p>
            <div class="mb-5 grid grid-cols-3 gap-2">
                @foreach (['upi' => ['UPI', 'smartphone'], 'card' => [__('Card'), 'credit-card'], 'netbanking' => [__('Net banking'), 'landmark']] as $value => [$label, $icon])
                    <button type="button" wire:key="pm-{{ $value }}" wire:click="$set('method', '{{ $value }}')" @class(['flex flex-col items-center gap-1 rounded-lg border px-3 py-3 text-sm transition', 'border-primary bg-primary-soft text-primary-ink' => $method === $value, 'border-line hover:border-primary/50' => $method !== $value])>
                        <x-ui.icon :name="$icon" class="size-5" />{{ $label }}
                    </button>
                @endforeach
            </div>
            <x-ui.alert tone="info" class="mb-5">{{ __('This is a simulated gateway for the demo. No money moves and no card or bank details are asked for. Choose the result you want to see.') }}</x-ui.alert>
            @if ($formError)<x-ui.alert tone="danger" class="mb-4">{{ $formError }}</x-ui.alert>@endif
            <div class="grid grid-cols-2 gap-3">
                <x-ui.button variant="secondary" icon="x" wire:click="complete('failure')" loading="complete">{{ __('Simulate failure') }}</x-ui.button>
                <x-ui.button icon="check" wire:click="complete('success')" loading="complete">{{ __('Simulate success') }}</x-ui.button>
            </div>
        @elseif ($payment['status'] === 'success')
            <div class="text-center">
                <x-ui.icon name="circle-check" class="mx-auto mb-2 size-12 text-success" />
                <p class="text-lg font-semibold text-ink">{{ __('Payment successful') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Transaction :r', ['r' => $payment['gateway_ref']]) }}</p>
                @if ($payment['receipt'])
                    <x-ui.button class="mt-4" icon="receipt" :href="route('fee-receipts.show', $payment['receipt']['id'])">{{ __('View receipt :n', ['n' => $payment['receipt']['receipt_no']]) }}</x-ui.button>
                @endif
            </div>
        @else
            <div class="text-center">
                <x-ui.icon name="circle-x" class="mx-auto mb-2 size-12 text-danger" />
                <p class="text-lg font-semibold text-ink">{{ __('Payment failed') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $payment['failure_reason'] }} {{ __('No money was taken.') }}</p>
                @if ($formError)<x-ui.alert tone="danger" class="mt-4">{{ $formError }}</x-ui.alert>@endif
                <x-ui.button class="mt-4" icon="rotate-ccw" wire:click="retry" loading="retry">{{ __('Try again') }}</x-ui.button>
            </div>
        @endif
    </x-ui.card>
</div>
