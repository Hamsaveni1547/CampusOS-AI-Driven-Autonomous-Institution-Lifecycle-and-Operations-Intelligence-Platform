<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fee counter: find a student, see their account, take a payment, add a charge, ask for a concession,
 * cancel a demand or a receipt.
 */
#[Layout('layouts::app')]
#[Title('Collect fees')]
class Collect extends Component
{
    use InteractsWithApi;

    #[Url(as: 'student', except: '')]
    public string $studentId = '';

    public string $search = '';

    /** @var array<string, mixed> */
    public array $payment = ['amount' => '', 'mode' => 'cash', 'reference' => '', 'paid_on' => ''];

    /** @var array<string, mixed> */
    public array $charge = ['title' => '', 'due_on' => '', 'fee_head_id' => '', 'amount' => ''];

    /** @var array<string, mixed> */
    public array $concession = ['demand_id' => '', 'title' => '', 'amount' => '', 'reason' => ''];

    /** @var array<string, string> */
    public array $cancel = ['type' => '', 'id' => '', 'label' => '', 'reason' => ''];

    public function choose(string $id): void
    {
        $this->studentId = $id;
        $this->search = '';
        $this->resetPayment();
    }

    public function collect(): void
    {
        $this->validate(['payment.amount' => 'required|numeric|min:1', 'payment.mode' => 'required|string', 'payment.paid_on' => 'required|date', 'payment.reference' => 'nullable|string|max:100']);
        $data = ['student_id' => $this->studentId, 'amount' => (float) $this->payment['amount'], 'mode' => $this->payment['mode'], 'reference' => $this->payment['reference'] ?: null, 'paid_on' => $this->payment['paid_on']];

        $this->callApi(fn () => $this->api()->post('fee-payments', $data), fieldMap: ['amount' => 'payment.amount', 'mode' => 'payment.mode', 'reference' => 'payment.reference', 'paid_on' => 'payment.paid_on'], onSuccess: function ($response): void {
            $this->toast('success', __('Receipt :no issued', ['no' => $response->data('receipt_no')]));
            $this->resetPayment();
        });
    }

    public function openCharge(): void
    {
        $this->charge = ['title' => '', 'due_on' => now()->addWeeks(2)->toDateString(), 'fee_head_id' => '', 'amount' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'add-charge');
    }

    public function addCharge(): void
    {
        $this->validate(['charge.title' => 'required|string|max:200', 'charge.due_on' => 'required|date', 'charge.fee_head_id' => 'required|string', 'charge.amount' => 'required|numeric|min:1']);
        $data = ['student_id' => $this->studentId, 'title' => $this->charge['title'], 'due_on' => $this->charge['due_on'], 'items' => [['fee_head_id' => $this->charge['fee_head_id'], 'amount' => (float) $this->charge['amount']]]];

        $this->callApi(fn () => $this->api()->post('fee-demands', $data), fieldMap: ['title' => 'charge.title', 'due_on' => 'charge.due_on'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'add-charge');
            $this->toast('success', __('Charge added'));
            $this->resetPayment();
        });
    }

    public function openConcession(string $demandId, string $title): void
    {
        $this->concession = ['demand_id' => $demandId, 'title' => $title, 'amount' => '', 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'concession');
    }

    public function requestConcession(): void
    {
        $this->validate(['concession.amount' => 'required|numeric|min:1', 'concession.reason' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('fee-demands/'.$this->concession['demand_id'].'/concessions', ['amount' => (float) $this->concession['amount'], 'reason' => $this->concession['reason']]),
            fieldMap: ['amount' => 'concession.amount', 'reason' => 'concession.reason'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'concession');
                $this->toast('success', __('Concession sent for approval'));
            });
    }

    public function askCancel(string $type, string $id, string $label): void
    {
        $this->cancel = ['type' => $type, 'id' => $id, 'label' => $label, 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'cancel-fee');
    }

    public function confirmCancel(): void
    {
        $this->validate(['cancel.reason' => 'required|string|max:500']);
        $path = ($this->cancel['type'] === 'payment' ? 'fee-payments/' : 'fee-demands/').$this->cancel['id'].'/cancel';

        $this->callApi(fn () => $this->api()->post($path, ['reason' => $this->cancel['reason']]), fieldMap: ['reason' => 'cancel.reason'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'cancel-fee');
            $this->toast('success', $this->cancel['type'] === 'payment' ? __('Receipt cancelled') : __('Demand cancelled'));
            $this->resetPayment();
        });
    }

    private function resetPayment(): void
    {
        $this->payment = ['amount' => '', 'mode' => 'cash', 'reference' => '', 'paid_on' => now()->toDateString()];
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('finance/options', memoize: true)->data();
        $account = $this->studentId !== '' ? (array) $this->api()->withoutCache(fn () => $this->api()->get('students/'.$this->studentId.'/fee-account'))->data() : null;

        if ($account !== null && $this->payment['amount'] === '' && $account['totals']['balance'] > 0) {
            $this->payment['amount'] = $account['totals']['balance'];
            $this->payment['paid_on'] = $this->payment['paid_on'] ?: now()->toDateString();
        }

        return view('livewire.fees.collect', [
            'matches' => strlen(trim($this->search)) >= 2 ? $this->api()->endpoint('students')->page(['search' => trim($this->search)], ['per_page' => 8])->items : [],
            'account' => $account,
            'modes' => collect($options['modes'])->mapWithKeys(fn (array $m) => [$m['value'] => $m['label']])->all(),
            'heads' => collect($options['heads'])->mapWithKeys(fn (array $h) => [$h['value'] => $h['label']])->all(),
        ]);
    }
}
