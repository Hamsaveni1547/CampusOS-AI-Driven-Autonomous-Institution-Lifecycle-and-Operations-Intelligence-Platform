<?php

namespace App\Livewire\Platform\Access;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One built-in role: its name, and a module-by-module grid of permission checkboxes.
 */
#[Layout('layouts::app')]
class RoleShow extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $roleId = '';

    /** @var list<string> permission names ticked */
    public array $granted = [];

    public string $displayName = '';

    public string $description = '';

    public function mount(string $role): void
    {
        $this->roleId = $role;
        $this->load();
    }

    private function load(): void
    {
        $role = $this->role();
        $this->granted = array_values($role['permissions']);
        $this->displayName = (string) $role['display_name'];
        $this->description = (string) ($role['description'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function role(): array
    {
        return (array) $this->api()->withoutCache(fn () => $this->api()->get('platform/access/roles/'.$this->roleId))->data();
    }

    /**
     * Tick or untick every permission of a module.
     */
    public function toggleModule(string $module, bool $on): void
    {
        $names = collect((array) $this->api()->get('platform/access/permissions', memoize: true)->data())->firstWhere('module', $module)['permissions'] ?? [];
        $names = array_column($names, 'name');
        $this->granted = array_values($on ? array_unique([...$this->granted, ...$names]) : array_diff($this->granted, $names));
    }

    public function save(): void
    {
        $this->validate(['displayName' => 'required|string|max:100', 'description' => 'nullable|string|max:300']);

        $this->callApi(function () {
            $this->api()->patch('platform/access/roles/'.$this->roleId, ['display_name' => $this->displayName, 'description' => $this->description ?: null]);

            return $this->api()->put('platform/access/roles/'.$this->roleId.'/permissions', ['permissions' => array_values(array_unique($this->granted))]);
        }, fieldMap: ['display_name' => 'displayName', 'description' => 'description'], onSuccess: function (): void {
            $this->load();
            $this->toast('success', __('Role saved'), __('Takes effect immediately for every institution.'));
        });
    }

    public function resetDefaults(): void
    {
        $this->callApi(fn () => $this->api()->post('platform/access/roles/'.$this->roleId.'/reset'), onSuccess: function (): void {
            $this->load();
            $this->toast('success', __('Role reset to its defaults'));
        });
    }

    public function render(): View
    {
        $role = $this->role();

        return view('livewire.platform.access.role-show', [
            'role' => $role,
            'groups' => (array) $this->api()->get('platform/access/permissions', memoize: true)->data(),
            'defaults' => $role['defaults'],
        ])->title($role['display_name']);
    }
}
