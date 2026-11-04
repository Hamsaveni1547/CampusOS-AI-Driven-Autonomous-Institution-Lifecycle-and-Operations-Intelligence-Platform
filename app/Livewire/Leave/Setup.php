<?php

namespace App\Livewire\Leave;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Leave types and the yearly allotment of balances.
 */
#[Layout('layouts::app')]
#[Title('Leave setup')]
class Setup extends Component
{
    use InteractsWithApi;

    /** @var array<string, mixed> */
    public array $type = [];

    public int $year;

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    public function open(?string $id = null): void
    {
        $existing = $id ? collect($this->types())->firstWhere('id', $id) : null;
        $this->type = [
            'id' => $id ?? '', 'code' => $existing['code'] ?? '', 'name' => $existing['name'] ?? '', 'days_per_year' => $existing['days_per_year'] ?? 12,
            'is_paid' => $existing['is_paid'] ?? true, 'allow_half_day' => $existing['allow_half_day'] ?? true, 'status' => $existing['status'] ?? 'active',
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'leave-type');
    }

    public function save(): void
    {
        $this->validate(['type.code' => 'required|string|max:10', 'type.name' => 'required|string|max:100', 'type.days_per_year' => 'required|numeric|min:0']);
        $t = $this->type;
        $data = ['code' => $t['code'], 'name' => $t['name'], 'days_per_year' => (float) $t['days_per_year'], 'is_paid' => (bool) $t['is_paid'], 'allow_half_day' => (bool) $t['allow_half_day'], 'status' => $t['status']];

        $this->callApi(fn () => $t['id'] ? $this->api()->patch('leave-types/'.$t['id'], $data) : $this->api()->post('leave-types', $data),
            fieldMap: ['code' => 'type.code', 'name' => 'type.name', 'days_per_year' => 'type.days_per_year'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'leave-type');
                $this->toast('success', __('Leave type saved'));
            });
    }

    public function allot(): void
    {
        $this->callApi(fn () => $this->api()->post('leave-balances/allot', ['year' => $this->year]), onSuccess: fn ($response) => $this->toast('success', (string) $response->data('message')));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function types(): array
    {
        return (array) $this->api()->get('leave-types')->data();
    }

    public function render(): View
    {
        return view('livewire.leave.setup', ['types' => $this->types()]);
    }
}
