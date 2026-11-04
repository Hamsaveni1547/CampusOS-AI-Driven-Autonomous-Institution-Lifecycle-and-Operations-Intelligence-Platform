<?php

namespace App\Livewire\Leave;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Staff self-service: leave balances, applying and cancelling.
 */
#[Layout('layouts::app')]
#[Title('My leave')]
class MyLeave extends Component
{
    use InteractsWithApi;

    /** @var array<string, mixed> */
    public array $form = ['leave_type_id' => '', 'from_date' => '', 'to_date' => '', 'half_day' => false, 'reason' => ''];

    public function open(): void
    {
        $this->form = ['leave_type_id' => '', 'from_date' => now()->addDay()->toDateString(), 'to_date' => now()->addDay()->toDateString(), 'half_day' => false, 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'apply-leave');
    }

    public function apply(): void
    {
        $this->validate(['form.leave_type_id' => 'required|string', 'form.from_date' => 'required|date', 'form.to_date' => 'required|date|after_or_equal:form.from_date', 'form.reason' => 'required|string|max:1000']);

        $this->callApi(fn () => $this->api()->post('me/leave-applications', [...$this->form, 'half_day' => (bool) $this->form['half_day']]),
            fieldMap: ['leave_type_id' => 'form.leave_type_id', 'from_date' => 'form.from_date', 'to_date' => 'form.to_date', 'reason' => 'form.reason'],
            onSuccess: function ($response): void {
                $this->dispatch('close-dialog', 'apply-leave');
                $this->toast('success', __('Applied for :d day(s)', ['d' => $response->data('days')]));
            });
    }

    public function cancel(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('me/leave-applications/'.$id.'/cancel'), onSuccess: fn () => $this->toast('success', __('Leave cancelled')));
    }

    public function render(): View
    {
        $data = (array) $this->api()->withoutCache(fn () => $this->api()->get('me/leave'))->data();

        return view('livewire.leave.my-leave', [
            'data' => $data,
            'types' => collect($data['balances'])->mapWithKeys(fn (array $b) => [$b['type']['id'] => $b['type']['name'].($b['available'] !== null ? ' ('.__(':n left', ['n' => $b['available'] + 0]).')' : '')])->all(),
            'halfDayTypes' => collect($data['balances'])->filter(fn (array $b) => $b['type']['allow_half_day'])->pluck('type.id')->all(),
        ]);
    }
}
