<div>
    <x-ui.page-header :title="__('My payslips')" :description="__('Payslips appear once the month’s payroll is finalised.')" />

    <x-ui.card :padding="false">
        @forelse ($payslips as $p)
            <a wire:key="mp-{{ $p['id'] }}" href="{{ route('payslips.show', $p['id']) }}" class="flex items-center gap-4 border-b border-line px-5 py-3 text-sm last:border-0 hover:bg-surface-muted">
                <span class="flex-1 font-medium text-ink">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $p['month'])->format('F Y') }}</span>
                <span class="text-xs text-ink-muted">{{ __('Gross ₹:g · deductions ₹:d', ['g' => number_format($p['gross']), 'd' => number_format($p['deductions'])]) }}</span>
                <span class="font-semibold text-ink">₹{{ number_format($p['net']) }}</span>
            </a>
        @empty
            <x-ui.empty-state compact icon="banknote" :title="__('No payslips yet')" />
        @endforelse
    </x-ui.card>
</div>
