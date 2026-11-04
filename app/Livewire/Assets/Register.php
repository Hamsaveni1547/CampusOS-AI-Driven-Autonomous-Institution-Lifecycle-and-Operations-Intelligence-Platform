<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Asset register: equipment with location, warranty and status, and each asset's repair history.
 */
#[Layout('layouts::app')]
#[Title('Assets')]
class Register extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, mixed>|null */
    public ?array $detail = null;

    protected function defaultFilters(): array
    {
        return ['category' => '', 'status' => '', 'warranty' => ''];
    }

    public function open(?string $id = null): void
    {
        $a = $id ? (array) $this->api()->get('assets/'.$id)->data() : [];
        $this->form = [
            'id' => $id ?? '', 'tag' => $a['tag'] ?? '', 'name' => $a['name'] ?? '', 'category' => $a['category'] ?? '',
            'department_id' => $a['department']['id'] ?? '', 'room_id' => $a['room']['id'] ?? '', 'make' => $a['make'] ?? '', 'model' => $a['model'] ?? '',
            'serial_no' => $a['serial_no'] ?? '', 'purchased_on' => $a['purchased_on'] ?? '', 'cost' => $a['cost'] ?? '', 'vendor' => $a['vendor'] ?? '',
            'warranty_until' => $a['warranty_until'] ?? '', 'status' => $a['status'] ?? 'working',
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'asset-form');
    }

    public function save(): void
    {
        $this->validate(['form.tag' => 'required|string|max:30', 'form.name' => 'required|string|max:150', 'form.category' => 'required|string|max:50', 'form.cost' => 'nullable|numeric|min:0']);
        $data = collect($this->form)->except('id')->map(fn ($v) => $v === '' ? null : $v)->all();
        $data['cost'] = $data['cost'] !== null ? (float) $data['cost'] : null;

        $this->callApi(fn () => $this->form['id'] ? $this->api()->patch('assets/'.$this->form['id'], $data) : $this->api()->post('assets', $data),
            fieldMap: ['tag' => 'form.tag', 'name' => 'form.name', 'category' => 'form.category'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'asset-form');
                $this->toast('success', __('Asset saved'));
            });
    }

    public function show(string $id): void
    {
        $this->detail = (array) $this->api()->withoutCache(fn () => $this->api()->get('assets/'.$id))->data();
        $this->dispatch('open-dialog', 'asset-detail');
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $summary = (array) $this->api()->withoutCache(fn () => $this->api()->get('assets/summary'))->data();

        return view('livewire.assets.register', [
            'rows' => $this->api()->endpoint('assets')->page($this->activeFilters(), $this->tableQuery()),
            'summary' => $summary,
            'categories' => collect($summary['categories'])->mapWithKeys(fn (string $c) => [$c => $c])->all(),
            'rooms' => $lookups->rooms(),
            'departments' => $lookups->departments(),
            'statuses' => ['working' => __('Working'), 'under_repair' => __('Under repair'), 'condemned' => __('Condemned')],
        ]);
    }
}
