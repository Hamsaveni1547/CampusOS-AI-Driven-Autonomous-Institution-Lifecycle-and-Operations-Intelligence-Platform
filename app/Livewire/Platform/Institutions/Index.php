<?php

namespace App\Livewire\Platform\Institutions;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Platform administrators: every institution, provisioning, edits and suspension.
 */
#[Layout('layouts::app')]
#[Title('Institutions')]
class Index extends Component
{
    use InteractsWithApi, WithTable;

    public ?string $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, string> */
    public array $admin = ['name' => '', 'email' => '', 'mobile' => ''];

    public ?string $suspendId = null;

    public string $reason = '';

    /** @return array<string, string> */
    public static function types(): array
    {
        return ['university' => __('University'), 'college' => __('College'), 'autonomous_college' => __('Autonomous college'), 'institute' => __('Institute'), 'school' => __('School')];
    }

    protected function defaultFilters(): array
    {
        return ['status' => '', 'type' => ''];
    }

    /** @return array<string, mixed> */
    private function emptyForm(): array
    {
        return ['code' => '', 'name' => '', 'short_name' => '', 'type' => 'college', 'parent_id' => '', 'email' => '', 'phone' => '', 'website' => '', 'city' => '', 'state' => '', 'country' => 'IN', 'timezone' => 'Asia/Kolkata', 'currency' => 'INR', 'affiliation' => ''];
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->form = $this->emptyForm();
        $this->admin = ['name' => '', 'email' => '', 'mobile' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'institution-form');
    }

    public function openEdit(string $id): void
    {
        $i = $this->api()->endpoint('platform/institutions')->find($id);
        $this->editingId = $id;
        $this->form = [
            'code' => $i['code'], 'name' => $i['name'], 'short_name' => (string) $i['short_name'], 'type' => $i['type'],
            'parent_id' => (string) ($i['parent']['id'] ?? ''), 'email' => (string) $i['email'], 'phone' => (string) $i['phone'],
            'website' => (string) $i['website'], 'city' => (string) $i['address']['city'], 'state' => (string) $i['address']['state'],
            'country' => $i['address']['country'], 'timezone' => $i['timezone'], 'currency' => $i['currency'], 'affiliation' => (string) $i['affiliation'],
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'institution-form');
    }

    public function save(): void
    {
        $rules = [
            'form.code' => 'required|string|max:32|regex:/^[A-Za-z0-9_-]+$/',
            'form.name' => 'required|string|max:255',
            'form.email' => 'nullable|email',
            'form.website' => 'nullable|url',
            'form.country' => 'required|size:2',
            'form.currency' => 'required|size:3',
            'form.timezone' => 'required|timezone:all',
        ];

        if ($this->editingId === null) {
            $rules += ['admin.name' => 'required|string|max:150', 'admin.email' => 'required|email', 'admin.mobile' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/']];
        }

        $this->validate($rules);

        $data = array_map(fn ($v) => $v === '' ? null : $v, $this->form);
        $data['country'] = strtoupper((string) $data['country']);
        $data['currency'] = strtoupper((string) $data['currency']);
        $endpoint = $this->api()->endpoint('platform/institutions');

        if ($this->editingId !== null) {
            unset($data['parent_id']);
            $call = fn () => $endpoint->update((string) $this->editingId, $data);
        } else {
            $data['admin'] = array_map(fn ($v) => $v === '' ? null : $v, $this->admin);
            $call = fn () => $endpoint->create(array_filter($data, fn ($v) => $v !== null));
        }

        $this->callApi($call, fieldMap: ['admin.name' => 'admin.name', 'admin.email' => 'admin.email', 'admin.mobile' => 'admin.mobile'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'institution-form');
            $this->toast('success', $this->editingId ? __('Institution updated') : __('Institution created'), $this->editingId ? null : __('The administrator was invited by email.'));
        });
    }

    public function askSuspend(string $id): void
    {
        $this->suspendId = $id;
        $this->reason = '';
        $this->dispatch('open-dialog', 'suspend-institution');
    }

    public function suspend(): void
    {
        $this->validate(['reason' => 'required|string|max:255']);

        $this->callApi(fn () => $this->api()->endpoint('platform/institutions')->action((string) $this->suspendId, 'suspend', ['reason' => $this->reason]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'suspend-institution');
            $this->toast('success', __('Institution suspended'));
        });
    }

    public function reactivate(string $id): void
    {
        $this->callApi(fn () => $this->api()->endpoint('platform/institutions')->action($id, 'reactivate'), onSuccess: fn () => $this->toast('success', __('Institution re-activated')));
    }

    public function render(): View
    {
        $universities = collect($this->api()->endpoint('platform/institutions')->page(['type' => 'university'], ['per_page' => 100])->items)
            ->reject(fn ($u) => $u['id'] === $this->editingId)
            ->mapWithKeys(fn ($u) => [$u['id'] => $u['code'].' · '.$u['name']])->all();

        return view('livewire.platform.institutions.index', [
            'rows' => $this->api()->endpoint('platform/institutions')->page($this->activeFilters(), $this->tableQuery()),
            'types' => self::types(),
            'universities' => $universities,
            'chips' => $this->filterChips([
                'status' => ['_name' => __('Status'), 'active' => __('Active'), 'suspended' => __('Suspended')],
                'type' => ['_name' => __('Type')] + self::types(),
            ]),
        ]);
    }
}
