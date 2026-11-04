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
 * Maintenance requests: staff report faults; the maintenance team assigns and resolves them.
 */
#[Layout('layouts::app')]
#[Title('Maintenance')]
class Maintenance extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, string> */
    public array $form = ['title' => '', 'description' => '', 'priority' => 'medium', 'asset' => '', 'room_id' => ''];

    /** @var array<string, mixed> */
    public array $update = ['id' => '', 'title' => '', 'status' => '', 'assigned_to' => '', 'resolution' => '', 'cost' => ''];

    protected function defaultFilters(): array
    {
        return ['status' => 'open'];
    }

    public function open(): void
    {
        $this->form = ['title' => '', 'description' => '', 'priority' => 'medium', 'asset' => '', 'room_id' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'raise-request');
    }

    public function raise(): void
    {
        $this->validate(['form.title' => 'required|string|max:200', 'form.priority' => 'required|string']);
        $data = collect($this->form)->map(fn ($v) => $v === '' ? null : $v)->all();

        $this->callApi(fn () => $this->api()->post('maintenance-requests', $data), fieldMap: ['title' => 'form.title', 'asset' => 'form.asset'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'raise-request');
            $this->toast('success', __('Request raised'));
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function openUpdate(string $id, string $status, array $row = []): void
    {
        $this->update = ['id' => $id, 'title' => $row['title'] ?? '', 'status' => $status, 'assigned_to' => $row['assigned_to'] ?? '', 'resolution' => '', 'cost' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'update-request');
    }

    public function saveUpdate(): void
    {
        $data = ['status' => $this->update['status'], 'assigned_to' => $this->update['assigned_to'] ?: null, 'resolution' => $this->update['resolution'] ?: null, 'cost' => $this->update['cost'] !== '' ? (float) $this->update['cost'] : null];

        $this->callApi(fn () => $this->api()->patch('maintenance-requests/'.$this->update['id'], $data), fieldMap: ['assigned_to' => 'update.assigned_to', 'resolution' => 'update.resolution'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'update-request');
            $this->toast('success', __('Request updated'));
        });
    }

    public function render(): View
    {
        return view('livewire.assets.maintenance', [
            'rows' => $this->api()->endpoint('maintenance-requests')->page($this->activeFilters(null), $this->tableQuery()),
            'rooms' => app(Lookups::class)->rooms(),
            'priorities' => ['low' => __('Low'), 'medium' => __('Medium'), 'high' => __('High')],
        ]);
    }
}
