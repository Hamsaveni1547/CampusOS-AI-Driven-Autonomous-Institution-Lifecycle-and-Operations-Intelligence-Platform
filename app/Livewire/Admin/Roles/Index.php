<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Built-in and custom roles, with a permission matrix for custom ones.
 */
#[Layout('layouts::app')]
#[Title('Roles & permissions')]
class Index extends Component
{
    use InteractsWithApi;

    public ?string $editingId = null;

    public bool $readOnly = false;

    /** @var array<string, mixed> */
    public array $form = ['display_name' => '', 'description' => '', 'is_department_scoped' => false, 'is_active' => true, 'permissions' => []];

    public ?string $deleteId = null;

    public function openCreate(): void
    {
        $this->reset('form', 'editingId', 'readOnly');
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'role-form');
    }

    public function open(string $roleId): void
    {
        $role = $this->api()->endpoint('roles')->find($roleId);
        $this->editingId = $roleId;
        $this->readOnly = (bool) $role['is_system'] || ! $this->can('identity.roles.manage');
        $this->form = [
            'display_name' => $role['display_name'],
            'description' => (string) $role['description'],
            'is_department_scoped' => (bool) $role['is_department_scoped'],
            'is_active' => (bool) $role['is_active'],
            'permissions' => array_values($role['permissions'] ?? []),
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'role-form');
    }

    /**
     * Tick or untick every permission of one module.
     *
     * @param  list<string>  $names
     */
    public function toggleModule(array $names, bool $on): void
    {
        $current = (array) $this->form['permissions'];
        $this->form['permissions'] = array_values($on ? array_unique([...$current, ...$names]) : array_diff($current, $names));
    }

    public function save(): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->validate(['form.display_name' => 'required|string|max:120', 'form.description' => 'nullable|string|max:500']);

        $data = [
            'display_name' => trim($this->form['display_name']),
            'description' => $this->form['description'] ?: null,
            'is_department_scoped' => (bool) $this->form['is_department_scoped'],
            'permissions' => array_values((array) $this->form['permissions']),
        ];

        $roles = $this->api()->endpoint('roles');

        $this->callApi(
            fn () => $this->editingId ? $roles->update($this->editingId, $data + ['is_active' => (bool) $this->form['is_active']]) : $roles->create($data),
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'role-form');
                $this->toast('success', $this->editingId ? __('Role updated') : __('Role created'));
            },
        );
    }

    public function askDelete(string $roleId): void
    {
        $this->deleteId = $roleId;
        $this->dispatch('open-dialog', 'delete-role');
    }

    public function delete(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->callApi(fn () => $this->api()->endpoint('roles')->delete($this->deleteId), toastErrors: true, onSuccess: function (): void {
            $this->deleteId = null;
            $this->dispatch('close-dialog', 'delete-role');
            $this->toast('success', __('Role deleted'));
        });
    }

    public function render(): View
    {
        return view('livewire.admin.roles.index', [
            'roles' => $this->api()->endpoint('roles')->all(memoize: false),
            'modules' => $this->api()->get('permissions', memoize: true)->data() ?? [],
        ]);
    }
}
