<?php

namespace App\Livewire\Payroll;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pay components and each employee's salary.
 */
#[Layout('layouts::app')]
#[Title('Salaries')]
class Salaries extends Component
{
    use InteractsWithApi;

    public string $search = '';

    /** @var array<string, mixed> */
    public array $pay = [];

    public string $employeeId = '';

    public string $employeeName = '';

    /** @var array<string, array{assigned: bool, amount: string|float|null}> */
    public array $salary = [];

    public function openComponent(?string $id = null): void
    {
        $existing = $id ? collect($this->components())->firstWhere('id', $id) : null;
        $this->pay = [
            'id' => $id ?? '', 'code' => $existing['code'] ?? '', 'name' => $existing['name'] ?? '', 'kind' => $existing['kind'] ?? 'earning',
            'calculation' => $existing['calculation'] ?? 'fixed', 'percent' => $existing['percent'] ?? '', 'max_amount' => $existing['max_amount'] ?? '',
            'affected_by_lop' => $existing['affected_by_lop'] ?? true, 'position' => $existing['position'] ?? 0, 'status' => $existing['status'] ?? 'active',
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'component-form');
    }

    public function saveComponent(): void
    {
        $this->validate(['pay.code' => 'required|string|max:20', 'pay.name' => 'required|string|max:100', 'pay.percent' => 'nullable|numeric|min:0|max:100', 'pay.max_amount' => 'nullable|numeric|min:0']);
        $c = $this->pay;
        $data = [
            'code' => $c['code'], 'name' => $c['name'], 'kind' => $c['kind'], 'calculation' => $c['calculation'],
            'percent' => $c['calculation'] === 'percent' && $c['percent'] !== '' ? (float) $c['percent'] : null,
            'max_amount' => $c['max_amount'] !== '' && $c['max_amount'] !== null ? (float) $c['max_amount'] : null,
            'affected_by_lop' => (bool) $c['affected_by_lop'], 'position' => (int) $c['position'], 'status' => $c['status'],
        ];

        $this->callApi(fn () => $c['id'] ? $this->api()->patch('pay-components/'.$c['id'], $data) : $this->api()->post('pay-components', $data),
            fieldMap: ['code' => 'pay.code', 'name' => 'pay.name', 'percent' => 'pay.percent'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'component-form');
                $this->toast('success', __('Pay component saved'));
            });
    }

    public function openSalary(string $employeeId): void
    {
        $data = (array) $this->api()->withoutCache(fn () => $this->api()->get('employees/'.$employeeId.'/salary'))->data();
        $this->employeeId = $employeeId;
        $this->employeeName = $data['employee']['name'];
        $this->salary = collect($data['components'])->mapWithKeys(fn (array $c) => [$c['id'] => ['assigned' => $c['assigned'], 'amount' => $c['amount'] ?? '']])->all();
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'salary-form');
    }

    public function saveSalary(): void
    {
        $components = collect($this->salary)->filter(fn (array $row) => $row['assigned'])
            ->map(fn (array $row, string $id) => ['id' => $id, 'amount' => $row['amount'] === '' || $row['amount'] === null ? null : (float) $row['amount']])->values()->all();

        $this->callApi(fn () => $this->api()->put('employees/'.$this->employeeId.'/salary', ['components' => $components]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'salary-form');
            $this->toast('success', __('Salary saved'));
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function components(): array
    {
        return (array) $this->api()->get('pay-components')->data();
    }

    public function render(): View
    {
        return view('livewire.payroll.salaries', [
            'components' => $this->components(),
            'employees' => (array) $this->api()->get('payroll/salaries', array_filter(['filter' => array_filter(['search' => trim($this->search)])]))->data(),
        ]);
    }
}
