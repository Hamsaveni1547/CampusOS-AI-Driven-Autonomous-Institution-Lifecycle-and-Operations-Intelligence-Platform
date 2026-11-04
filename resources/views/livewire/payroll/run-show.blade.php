@php($money = fn ($v) => '₹'.number_format((float) $v, 0))
<div>
    <x-ui.page-header :title="__('Payroll — :m', ['m' => $monthName])" :back="route('payroll-runs.index')" :back-label="__('Payroll')">
        <x-slot:meta><x-ui.status-badge :status="$run['status']" /></x-slot:meta>
        <x-slot:actions>
            @if ($run['can']['edit'])
                <x-ui.button variant="ghost" icon="trash-2" wire:click="delete" wire:confirm="{{ __('Delete this payroll?') }}">{{ __('Delete') }}</x-ui.button>
                <x-ui.button icon="calculator" wire:click="step('compute')" loading="step">{{ $run['status'] === 'computed' ? __('Calculate again') : __('Calculate') }}</x-ui.button>
            @endif
            @if ($run['can']['approve'])<x-ui.button icon="check" wire:click="step('approve')">{{ __('Approve') }}</x-ui.button>@endif
            @if ($run['can']['finalize'])<x-ui.button icon="lock" x-on:click="$dispatch('open-dialog', 'finalize-run')">{{ __('Finalise') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat :label="__('Gross')" :value="$money($run['totals']['gross'])" icon="banknote" />
        <x-ui.stat :label="__('Deductions')" :value="$money($run['totals']['deductions'])" icon="minus" tone="warning" />
        <x-ui.stat :label="__('Net pay')" :value="$money($run['totals']['net'])" icon="wallet" tone="success" :hint="$run['computed_by'] ? __('calculated by :n', ['n' => $run['computed_by']]).($run['approved_by'] ? ' · '.__('approved by :n', ['n' => $run['approved_by']]) : '') : null" />
    </div>

    @if ($run['status'] === 'draft' && $run['totals']['net'] > 0)
        <x-ui.alert class="mb-4" tone="warning">{{ __('Inputs changed since the last calculation. Calculate again before approval.') }}</x-ui.alert>
    @endif

    <x-ui.table :empty="$run['payslips'] === []">
        <x-slot:head>
            <x-ui.th>{{ __('Employee') }}</x-ui.th>
            <x-ui.th>{{ __('LOP days') }}</x-ui.th>
            <x-ui.th>{{ __('Gross') }}</x-ui.th>
            <x-ui.th>{{ __('Deductions') }}</x-ui.th>
            <x-ui.th>{{ __('Net') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($run['payslips'] as $slip)
            <x-ui.tr wire:key="ps-{{ $slip['id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('payslips.show', $slip['id']) }}" class="font-medium text-ink hover:underline">{{ $slip['employee']['name'] }}</a>
                    <span class="block font-mono text-xs text-ink-subtle">{{ $slip['employee']['code'] }} · {{ $slip['employee']['department'] ?? '—' }}</span>
                    @foreach ($slip['adjustments'] as $a)<span class="block text-xs text-ink-muted">{{ $a['kind'] === 'earning' ? '+' : '−' }} {{ $a['label'] }} {{ $money($a['amount']) }}</span>@endforeach
                </x-ui.td>
                <x-ui.td :label="__('LOP days')">{{ $slip['lop_days'] + 0 }}</x-ui.td>
                <x-ui.td :label="__('Gross')">{{ $money($slip['gross']) }}</x-ui.td>
                <x-ui.td :label="__('Deductions')">{{ $money($slip['deductions']) }}</x-ui.td>
                <x-ui.td :label="__('Net')"><span class="font-semibold">{{ $money($slip['net']) }}</span></x-ui.td>
                <x-ui.td class="text-right">
                    @if ($run['can']['edit'])
                        <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="openInputs('{{ $slip['id'] }}')">{{ __('Inputs') }}</x-ui.button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No employees with a salary')" :description="__('Set salaries first, then start the payroll.')" /></x-slot:emptyState>
    </x-ui.table>

    @if ($run['by_department'] !== [])
        <x-ui.card class="mt-6" :title="__('Net pay by department')" :padding="false">
            @foreach ($run['by_department'] as $department => $amount)
                <div wire:key="bd-{{ $loop->index }}" class="flex justify-between border-b border-line px-5 py-2 text-sm last:border-0"><span>{{ $department }}</span><span class="font-medium">{{ $money($amount) }}</span></div>
            @endforeach
        </x-ui.card>
    @endif

    <x-ui.modal name="payslip-inputs" :title="$inputs['name']">
        <form wire:submit="saveInputs" id="payslip-inputs-form" class="space-y-4">
            <div class="w-40"><x-ui.input type="number" step="0.5" min="0" :label="__('Loss-of-pay days')" wire:model="inputs.lop_days" required /></div>
            <div class="space-y-2">
                <p class="text-sm font-medium text-ink">{{ __('One-off amounts this month') }}</p>
                @foreach ($inputs['adjustments'] as $i => $a)
                    <div wire:key="adj-{{ $i }}" class="grid grid-cols-[8rem_1fr_8rem_auto] items-end gap-2">
                        <x-ui.select wire:model="inputs.adjustments.{{ $i }}.kind" :options="['earning' => __('Add'), 'deduction' => __('Deduct')]" />
                        <x-ui.input wire:model="inputs.adjustments.{{ $i }}.label" :placeholder="__('e.g. Exam duty honorarium')" />
                        <x-ui.input type="number" min="1" prefix="₹" wire:model="inputs.adjustments.{{ $i }}.amount" />
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeAdjustment({{ $i }})"><span class="sr-only">{{ __('Remove') }}</span></x-ui.button>
                    </div>
                @endforeach
                <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="addAdjustment">{{ __('Add amount') }}</x-ui.button>
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'payslip-inputs')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="payslip-inputs-form" loading="saveInputs">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="finalize-run" :title="__('Finalise payroll')" size="sm">
        <form wire:submit="step('finalize')" id="finalize-run-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ __('Finalised payroll cannot change. Staff will see their payslips.') }}</p>
            <x-ui.input type="date" :label="__('Paid on')" wire:model="paidOn" required />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'finalize-run')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="finalize-run-form" loading="step">{{ __('Finalise') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
