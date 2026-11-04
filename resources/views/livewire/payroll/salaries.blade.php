@php($money = fn ($v) => '₹'.number_format((float) $v, 0))
<div>
    <x-ui.page-header :title="__('Salaries')" :description="__('Pay components, and the salary of each employee (full-month figures).')">
        @canapi('payroll.salaries.manage')
            <x-slot:actions><x-ui.button variant="secondary" icon="plus" wire:click="openComponent">{{ __('Pay component') }}</x-ui.button></x-slot:actions>
        @endcanapi
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card :title="__('Pay components')" :padding="false">
            @forelse ($components as $c)
                <button type="button" wire:key="pc-{{ $c['id'] }}" wire:click="openComponent('{{ $c['id'] }}')" class="flex w-full items-center gap-3 border-b border-line px-5 py-2.5 text-left text-sm last:border-0 hover:bg-surface-muted">
                    <span class="w-14 font-mono text-xs text-ink-muted">{{ $c['code'] }}</span>
                    <span class="flex-1 text-ink">{{ $c['name'] }}<span class="block text-xs text-ink-muted">{{ $c['calculation'] === 'percent' ? __(':p% of basic', ['p' => $c['percent'] + 0]) : __('fixed amount') }}@if ($c['max_amount']) · {{ __('max :m', ['m' => $money($c['max_amount'])]) }}@endif</span></span>
                    <x-ui.badge size="sm" :tone="$c['kind'] === 'earning' ? 'success' : 'danger'">{{ $c['kind'] === 'earning' ? __('Earning') : __('Deduction') }}</x-ui.badge>
                </button>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('Start with BASIC (fixed earning), then DA, HRA, PF, PT…') }}</p>
            @endforelse
        </x-ui.card>

        <div class="lg:col-span-2">
            <div class="mb-4 w-72"><x-ui.input icon="search" wire:model.live.debounce.300ms="search" :placeholder="__('Name or employee code')" /></div>
            <x-ui.table :empty="$employees === []">
                <x-slot:head>
                    <x-ui.th>{{ __('Employee') }}</x-ui.th>
                    <x-ui.th>{{ __('Department') }}</x-ui.th>
                    <x-ui.th>{{ __('Gross') }}</x-ui.th>
                    <x-ui.th>{{ __('Net') }}</x-ui.th>
                    <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
                </x-slot:head>
                @foreach ($employees as $e)
                    <x-ui.tr wire:key="se-{{ $e['id'] }}">
                        <x-ui.td primary><span class="font-medium text-ink">{{ $e['name'] }}</span><span class="block font-mono text-xs text-ink-subtle">{{ $e['code'] }} · {{ $e['designation'] }}</span></x-ui.td>
                        <x-ui.td :label="__('Department')">{{ $e['department'] ?? '—' }}</x-ui.td>
                        <x-ui.td :label="__('Gross')">{{ $e['gross'] !== null ? $money($e['gross']) : '—' }}</x-ui.td>
                        <x-ui.td :label="__('Net')"><span class="font-semibold">{{ $e['net'] !== null ? $money($e['net']) : __('Not set') }}</span></x-ui.td>
                        <x-ui.td class="text-right">
                            @canapi('payroll.salaries.manage')
                                <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="openSalary('{{ $e['id'] }}')">{{ __('Salary') }}</x-ui.button>
                            @endcanapi
                        </x-ui.td>
                    </x-ui.tr>
                @endforeach
                <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No employees')" /></x-slot:emptyState>
            </x-ui.table>
        </div>
    </div>

    <x-ui.modal name="component-form" :title="($pay['id'] ?? '') ? __('Edit pay component') : __('New pay component')">
        <form wire:submit="saveComponent" id="component-form-el" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('Code')" wire:model="pay.code" required placeholder="HRA" :hint="__('BASIC is the base for percentages.')" />
            <x-ui.input :label="__('Name')" wire:model="pay.name" required />
            <x-ui.select :label="__('Type')" wire:model="pay.kind" :options="['earning' => __('Earning'), 'deduction' => __('Deduction')]" required />
            <x-ui.select :label="__('Calculated as')" wire:model.live="pay.calculation" :options="['fixed' => __('Fixed amount per employee'), 'percent' => __('Percentage of basic')]" required />
            @if (($pay['calculation'] ?? '') === 'percent')
                <x-ui.input type="number" step="0.001" min="0" max="100" :label="__('Percent')" suffix="%" wire:model="pay.percent" required />
            @endif
            <x-ui.input type="number" step="0.01" min="0" :label="__('Maximum amount')" prefix="₹" wire:model="pay.max_amount" optional :hint="__('e.g. PF capped at 1,800')" />
            <x-ui.input type="number" min="0" :label="__('Order on payslip')" wire:model="pay.position" />
            <x-ui.select :label="__('Status')" wire:model="pay.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
            <x-ui.switch wire:model="pay.affected_by_lop" :label="__('Reduce for loss-of-pay days')" class="sm:col-span-2" />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'component-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="component-form-el" loading="saveComponent">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="salary-form" :title="__('Salary of :n', ['n' => $employeeName])">
        <form wire:submit="saveSalary" id="salary-form-el" class="space-y-3">
            @foreach ($components as $c)
                <div wire:key="sf-{{ $c['id'] }}" class="grid grid-cols-[1fr_10rem] items-center gap-3">
                    <x-ui.checkbox wire:model.live="salary.{{ $c['id'] }}.assigned" :label="$c['name']" :description="$c['calculation'] === 'percent' ? __(':p% of basic', ['p' => $c['percent'] + 0]) : null" />
                    @if ($c['calculation'] === 'fixed' && ($salary[$c['id']]['assigned'] ?? false))
                        <x-ui.input type="number" step="0.01" min="0" prefix="₹" wire:model="salary.{{ $c['id'] }}.amount" />
                    @endif
                </div>
            @endforeach
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'salary-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="salary-form-el" loading="saveSalary">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
