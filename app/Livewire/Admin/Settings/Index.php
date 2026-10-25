<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Institution settings, one group at a time. Fields come from the API's registry
 * (int, bool, string, array), so new settings appear without frontend changes.
 */
#[Layout('layouts::app')]
#[Title('Settings')]
class Index extends Component
{
    use InteractsWithApi;

    #[Url(as: 'group', except: '')]
    public string $group = '';

    /** @var array<string, mixed> */
    public array $values = [];

    /**
     * API path prefix; the platform screen overrides it.
     */
    protected function basePath(): string
    {
        return 'settings';
    }

    protected function managePermission(): ?string
    {
        return 'admin.settings.manage';
    }

    public function mount(): void
    {
        $this->loadGroup();
    }

    public function updatedGroup(): void
    {
        $this->loadGroup();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groups(): array
    {
        return (array) $this->api()->get($this->basePath(), memoize: true)->data();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function current(): ?array
    {
        return collect($this->groups())->firstWhere('group', $this->group);
    }

    private function loadGroup(): void
    {
        $groups = $this->groups();

        if ($this->group === '' || ! collect($groups)->contains('group', $this->group)) {
            $this->group = (string) ($groups[0]['group'] ?? '');
        }

        $this->values = [];

        foreach ($this->current()['settings'] ?? [] as $setting) {
            $this->values[$setting['key']] = match ($setting['type']) {
                'bool' => (bool) $setting['value'],
                'array' => implode(', ', (array) $setting['value']),
                'int' => (string) $setting['value'],
                default => $setting['secret'] ? '' : (string) $setting['value'],
            };
        }

        $this->resetErrorBag();
    }

    public function save(): void
    {
        $payload = [];

        foreach ($this->current()['settings'] ?? [] as $setting) {
            $value = $this->values[$setting['key']] ?? null;

            if ($setting['secret'] && ($value === '' || $value === null)) {
                continue;
            }

            $payload[$setting['key']] = match ($setting['type']) {
                'bool' => (bool) $value,
                'int' => is_numeric($value) ? (int) $value : $value,
                'array' => array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($item) => $item !== '')),
                default => $value === '' ? null : $value,
            };
        }

        $this->callApi(
            fn () => $this->api()->put($this->basePath().'/'.$this->group, $payload),
            fieldMap: collect(array_keys($payload))->mapWithKeys(fn ($key) => [$key => 'values.'.$key])->all(),
            onSuccess: function (): void {
                $this->api()->forgetMemo();
                $this->loadGroup();
                $this->toast('success', __('Settings saved'));
            },
        );
    }

    public function render(): View
    {
        $permission = $this->managePermission();

        return view('livewire.admin.settings.index', [
            'groups' => $this->groups(),
            'current' => $this->current(),
            'canManage' => $permission === null || $this->can($permission),
        ]);
    }
}
