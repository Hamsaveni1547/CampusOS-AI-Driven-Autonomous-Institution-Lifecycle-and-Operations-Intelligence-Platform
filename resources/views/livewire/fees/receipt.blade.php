@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div>
    <div class="mb-4 flex justify-end print:hidden">
        <x-ui.button icon="printer" x-on:click="window.print()">{{ __('Print') }}</x-ui.button>
    </div>

    <div class="mx-auto max-w-2xl rounded-lg border border-line bg-surface p-8 print:border-0 print:p-0">
        <div class="mb-6 border-b border-line pb-4 text-center">
            <p class="text-lg font-semibold text-ink">{{ $institution }}</p>
            <p class="text-sm text-ink-muted">{{ __('Fee receipt') }}</p>
        </div>

        @if ($receipt['status'] === 'cancelled')
            <x-ui.alert class="mb-4" tone="danger">{{ __('Cancelled: :r', ['r' => $receipt['cancel_reason']]) }}</x-ui.alert>
        @endif

        <x-ui.description-list>
            <x-ui.description-item :label="__('Receipt no.')"><span class="font-mono">{{ $receipt['receipt_no'] }}</span></x-ui.description-item>
            <x-ui.description-item :label="__('Date')">{{ \App\Support\Format::date($receipt['paid_on']) }}</x-ui.description-item>
            <x-ui.description-item :label="__('Student')">{{ $receipt['student']['name'] }} <span class="font-mono text-xs text-ink-muted">{{ $receipt['student']['register_no'] }}</span></x-ui.description-item>
            <x-ui.description-item :label="__('Programme')">{{ $receipt['programme'] }}</x-ui.description-item>
            <x-ui.description-item :label="__('Mode')">{{ $mode }}@if ($receipt['reference']) · {{ $receipt['reference'] }}@endif</x-ui.description-item>
        </x-ui.description-list>

        <table class="mt-6 w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase text-ink-muted"><tr><th class="py-2">{{ __('Towards') }}</th><th class="py-2 text-right">{{ __('Amount') }}</th></tr></thead>
            <tbody>
                @foreach ($receipt['paid_for'] as $line)
                    <tr wire:key="rl-{{ $loop->index }}" class="border-b border-line"><td class="py-2">{{ $line['demand'] }}</td><td class="py-2 text-right">{{ $money($line['amount']) }}</td></tr>
                @endforeach
                <tr><td class="py-2 font-semibold">{{ __('Total received') }}</td><td class="py-2 text-right font-semibold">{{ $money($receipt['amount']) }}</td></tr>
            </tbody>
        </table>

        <p class="mt-4 text-sm text-ink-muted">{{ __('Balance after this payment: :b', ['b' => $money($receipt['balance_after'])]) }}</p>
        <p class="mt-10 text-right text-xs text-ink-muted">{{ __('Received by :n', ['n' => $receipt['received_by'] ?? '—']) }}</p>
    </div>
</div>
