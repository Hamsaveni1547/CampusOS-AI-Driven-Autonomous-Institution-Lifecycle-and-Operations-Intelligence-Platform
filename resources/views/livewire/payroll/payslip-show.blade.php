@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
@php($earnings = collect($slip['lines'])->where('kind', 'earning'))
@php($deductions = collect($slip['lines'])->where('kind', 'deduction'))
<div>
    <div class="mb-4 flex justify-end print:hidden"><x-ui.button variant="secondary" icon="download" :href="route('documents.pdf', ['payslip', $payslipId])">{{ __('Download PDF') }}</x-ui.button> <x-ui.button icon="printer" x-on:click="window.print()">{{ __('Print') }}</x-ui.button></div>

    <div class="mx-auto max-w-3xl rounded-lg border border-line bg-surface p-8 print:border-0 print:p-0">
        <div class="mb-6 border-b border-line pb-4 text-center">
            <p class="text-lg font-semibold text-ink">{{ $institution }}</p>
            <p class="text-sm text-ink-muted">{{ __('Payslip for :m', ['m' => $monthName]) }}</p>
        </div>

        <x-ui.description-list>
            <x-ui.description-item :label="__('Employee')">{{ $slip['employee']['name'] }} <span class="font-mono text-xs text-ink-muted">{{ $slip['employee']['code'] }}</span></x-ui.description-item>
            <x-ui.description-item :label="__('Designation')">{{ $slip['designation'] ?? '—' }}</x-ui.description-item>
            <x-ui.description-item :label="__('Department')">{{ $slip['employee']['department'] ?? '—' }}</x-ui.description-item>
            <x-ui.description-item :label="__('Days paid')">{{ $slip['days_in_month'] - $slip['lop_days'] }} / {{ $slip['days_in_month'] }}@if ($slip['lop_days'] > 0) <span class="text-xs text-ink-muted">({{ __(':d LOP', ['d' => $slip['lop_days'] + 0]) }})</span>@endif</x-ui.description-item>
            @if ($slip['run']['paid_on'])<x-ui.description-item :label="__('Paid on')">{{ \App\Support\Format::date($slip['run']['paid_on']) }}</x-ui.description-item>@endif
        </x-ui.description-list>

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            @foreach ([[__('Earnings'), $earnings, $slip['gross']], [__('Deductions'), $deductions, $slip['deductions']]] as [$title, $lines, $total])
                <table wire:key="pt-{{ $loop->index }}" class="w-full text-left text-sm">
                    <thead class="border-b border-line text-xs uppercase text-ink-muted"><tr><th class="py-2">{{ $title }}</th><th class="py-2 text-right">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $line)<tr class="border-b border-line"><td class="py-1.5">{{ $line['name'] }}</td><td class="py-1.5 text-right">{{ $money($line['amount']) }}</td></tr>@endforeach
                        <tr><td class="py-2 font-semibold">{{ __('Total') }}</td><td class="py-2 text-right font-semibold">{{ $money($total) }}</td></tr>
                    </tbody>
                </table>
            @endforeach
        </div>

        <p class="mt-6 rounded-md bg-surface-muted px-4 py-3 text-right text-base font-semibold text-ink">{{ __('Net pay: :n', ['n' => $money($slip['net'])]) }}</p>
        <p class="mt-6 text-center text-xs text-ink-subtle">{{ __('This is a system-generated payslip.') }}</p>
    </div>
</div>
