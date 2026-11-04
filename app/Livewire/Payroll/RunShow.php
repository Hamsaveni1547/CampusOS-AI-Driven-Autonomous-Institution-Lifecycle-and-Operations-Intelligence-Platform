<?php

namespace App\Livewire\Payroll;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One month's payroll: salary register, inputs per employee, calculate, approve and finalise.
 */
#[Layout('layouts::app')]
class RunShow extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $runId = '';

    /** @var array<string, mixed> */
    public array $inputs = ['payslip_id' => '', 'name' => '', 'lop_days' => 0, 'adjustments' => []];

    public string $paidOn = '';

    public function mount(string $run): void
    {
        $this->runId = $run;
        $this->paidOn = now()->toDateString();
    }

    public function step(string $action): void
    {
        $data = $action === 'finalize' ? ['paid_on' => $this->paidOn] : [];
        $messages = ['compute' => __('Payroll calculated'), 'approve' => __('Payroll approved'), 'finalize' => __('Payroll finalised; payslips are now visible to staff')];

        $this->callApi(fn () => $this->api()->post('payroll-runs/'.$this->runId.'/'.$action, $data), onSuccess: function () use ($action, $messages): void {
            $this->dispatch('close-dialog', 'finalize-run');
            $this->toast('success', $messages[$action]);
        });
    }

    public function delete(): void
    {
        $this->callApi(fn () => $this->api()->delete('payroll-runs/'.$this->runId), onSuccess: fn () => $this->redirectRoute('payroll-runs.index', navigate: true));
    }

    public function openInputs(string $payslipId): void
    {
        $slip = collect($this->run()['payslips'])->firstWhere('id', $payslipId);
        $this->inputs = ['payslip_id' => $payslipId, 'name' => $slip['employee']['name'], 'lop_days' => $slip['lop_days'], 'adjustments' => $slip['adjustments']];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'payslip-inputs');
    }

    public function addAdjustment(): void
    {
        $this->inputs['adjustments'][] = ['kind' => 'earning', 'label' => '', 'amount' => ''];
    }

    public function removeAdjustment(int $index): void
    {
        unset($this->inputs['adjustments'][$index]);
        $this->inputs['adjustments'] = array_values($this->inputs['adjustments']);
    }

    public function saveInputs(): void
    {
        $this->validate(['inputs.lop_days' => 'required|numeric|min:0|max:31', 'inputs.adjustments.*.label' => 'required|string|max:100', 'inputs.adjustments.*.amount' => 'required|numeric|min:1']);
        $data = ['lop_days' => (float) $this->inputs['lop_days'], 'adjustments' => array_map(fn (array $a) => ['kind' => $a['kind'], 'label' => $a['label'], 'amount' => (float) $a['amount']], $this->inputs['adjustments'])];

        $this->callApi(fn () => $this->api()->put('payslips/'.$this->inputs['payslip_id'].'/inputs', $data), fieldMap: ['lop_days' => 'inputs.lop_days'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'payslip-inputs');
            $this->toast('success', __('Saved. Calculate the payroll again.'));
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function run(): array
    {
        return (array) $this->api()->withoutCache(fn () => $this->api()->get('payroll-runs/'.$this->runId))->data();
    }

    public function render(): View
    {
        $run = $this->run();

        return view('livewire.payroll.run-show', ['run' => $run, 'monthName' => Carbon::createFromFormat('Y-m', $run['month'])->format('F Y')])
            ->title(__('Payroll :m', ['m' => $run['month']]));
    }
}
