<div>
    <x-ui.page-header :title="__('Payroll')" :description="__('One run per month: enter loss of pay and one-off amounts, calculate, get it approved, then finalise.')" />

    @canapi('payroll.runs.process')
        <form wire:submit="create" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="w-48"><x-ui.input type="month" :label="__('Month')" wire:model="month" required /></div>
            <x-ui.button type="submit" icon="plus" loading="create">{{ __('Start payroll') }}</x-ui.button>
        </form>
    @endcanapi

    <x-ui.table :empty="$runs === []">
        <x-slot:head>
            <x-ui.th>{{ __('Month') }}</x-ui.th>
            <x-ui.th>{{ __('Employees') }}</x-ui.th>
            <x-ui.th>{{ __('Net pay') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
        </x-slot:head>
        @foreach ($runs as $run)
            <x-ui.tr wire:key="run-{{ $run['id'] }}">
                <x-ui.td primary><a href="{{ route('payroll-runs.show', $run['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $run['month'])->format('F Y') }}</a>@if ($run['paid_on'])<span class="block text-xs text-ink-muted">{{ __('Paid :d', ['d' => \App\Support\Format::date($run['paid_on'])]) }}</span>@endif</x-ui.td>
                <x-ui.td :label="__('Employees')">{{ $run['employees'] }}</x-ui.td>
                <x-ui.td :label="__('Net pay')">₹{{ number_format($run['net_total'], 0) }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$run['status']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="banknote" :title="__('No payroll yet')" /></x-slot:emptyState>
    </x-ui.table>
</div>
