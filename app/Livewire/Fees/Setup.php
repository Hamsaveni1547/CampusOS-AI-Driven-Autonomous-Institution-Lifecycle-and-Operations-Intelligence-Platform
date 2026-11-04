<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fee heads and fee structures, and raising a structure's fees for its students.
 */
#[Layout('layouts::app')]
#[Title('Fee setup')]
class Setup extends Component
{
    use InteractsWithApi;

    /** @var array<string, string> */
    public array $head = ['id' => '', 'code' => '', 'name' => '', 'description' => ''];

    /** @var array<string, mixed> */
    public array $structure = [];

    public function openHead(?string $id = null): void
    {
        $existing = $id ? collect($this->heads())->firstWhere('id', $id) : null;
        $this->head = ['id' => $id ?? '', 'code' => $existing['code'] ?? '', 'name' => $existing['name'] ?? '', 'description' => $existing['description'] ?? ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'head-form');
    }

    public function saveHead(): void
    {
        $this->validate(['head.code' => 'required|string|max:20', 'head.name' => 'required|string|max:100']);
        $data = ['code' => $this->head['code'], 'name' => $this->head['name'], 'description' => $this->head['description'] ?: null];

        $this->callApi(fn () => $this->head['id'] ? $this->api()->patch('fee-heads/'.$this->head['id'], $data) : $this->api()->post('fee-heads', $data),
            fieldMap: ['code' => 'head.code', 'name' => 'head.name'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'head-form');
                $this->toast('success', __('Fee head saved'));
            });
    }

    public function openStructure(?string $id = null): void
    {
        $existing = $id ? collect($this->structures())->firstWhere('id', $id) : null;
        $this->structure = [
            'id' => $id ?? '', 'name' => $existing['name'] ?? '', 'programme_id' => $existing['programme']['id'] ?? '',
            'admission_year' => $existing['admission_year'] ?? '', 'quota' => $existing['quota'] ?? '', 'term_number' => $existing['term_number'] ?? '',
            'due_on' => $existing['due_on'] ?? now()->addWeeks(3)->toDateString(),
            'items' => $existing ? collect($existing['items'])->map(fn (array $i) => ['fee_head_id' => $i['fee_head_id'], 'amount' => $i['amount']])->all() : [['fee_head_id' => '', 'amount' => '']],
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'structure-form');
    }

    public function addItem(): void
    {
        $this->structure['items'][] = ['fee_head_id' => '', 'amount' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->structure['items'][$index]);
        $this->structure['items'] = array_values($this->structure['items']);
    }

    public function saveStructure(): void
    {
        $this->validate([
            'structure.name' => 'required|string|max:150', 'structure.programme_id' => 'required|string', 'structure.due_on' => 'required|date',
            'structure.admission_year' => 'nullable|integer', 'structure.term_number' => 'nullable|integer|min:1',
            'structure.items' => 'required|array|min:1', 'structure.items.*.fee_head_id' => 'required|string', 'structure.items.*.amount' => 'required|numeric|min:1',
        ]);
        $s = $this->structure;
        $data = [
            'name' => $s['name'], 'programme_id' => $s['programme_id'], 'due_on' => $s['due_on'],
            'admission_year' => $s['admission_year'] !== '' ? (int) $s['admission_year'] : null, 'quota' => $s['quota'] ?: null,
            'term_number' => $s['term_number'] !== '' ? (int) $s['term_number'] : null,
            'items' => array_map(fn (array $i) => ['fee_head_id' => $i['fee_head_id'], 'amount' => (float) $i['amount']], $s['items']),
        ];

        $this->callApi(fn () => $s['id'] ? $this->api()->patch('fee-structures/'.$s['id'], $data) : $this->api()->post('fee-structures', $data),
            fieldMap: ['name' => 'structure.name', 'programme_id' => 'structure.programme_id', 'due_on' => 'structure.due_on', 'items' => 'structure.items'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'structure-form');
                $this->toast('success', __('Fee structure saved'));
            });
    }

    public function generate(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('fee-structures/'.$id.'/generate'), onSuccess: fn ($response) => $this->toast('success', (string) $response->data('message')));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function heads(): array
    {
        return (array) $this->api()->get('fee-heads')->data();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function structures(): array
    {
        return (array) $this->api()->get('fee-structures')->data();
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('finance/options', memoize: true)->data();
        $heads = $this->heads();

        return view('livewire.fees.setup', [
            'heads' => $heads,
            'structures' => $this->structures(),
            'headOptions' => collect($heads)->where('status', 'active')->mapWithKeys(fn (array $h) => [$h['id'] => $h['name']])->all(),
            'programmes' => app(Lookups::class)->programmes(),
            'quotas' => collect($options['quotas'])->mapWithKeys(fn (array $q) => [$q['value'] => $q['label']])->all(),
        ]);
    }
}
