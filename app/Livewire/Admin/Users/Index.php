<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Users')]
class Index extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $form = ['name' => '', 'email' => '', 'mobile' => '', 'roles' => [], 'send_invite' => true];

    public ?string $temporaryPassword = null;

    protected function defaultFilters(): array
    {
        return ['status' => '', 'role' => '', 'locked' => ''];
    }

    public function openCreate(): void
    {
        $this->reset('form', 'temporaryPassword');
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'user-form');
    }

    public function create(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:150'],
            'form.email' => ['required', 'email', 'max:255'],
            'form.mobile' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
        ]);

        $response = $this->callApi(fn () => $this->api()->endpoint('users')->create(array_filter([
            'name' => trim($this->form['name']),
            'email' => trim($this->form['email']),
            'mobile' => $this->form['mobile'] ?: null,
            'roles' => array_values((array) $this->form['roles']),
            'send_invite' => (bool) $this->form['send_invite'],
        ], fn ($v) => $v !== null)));

        if ($response === null) {
            return;
        }

        $this->dispatch('close-dialog', 'user-form');
        $this->temporaryPassword = $response->json('meta.temporary_password');

        if ($response->json('meta.linked_existing_account')) {
            $this->toast('success', __('Existing account added to this institution'));
        } elseif ($this->temporaryPassword) {
            $this->dispatch('open-dialog', 'temporary-password');
        } else {
            $this->toast('success', __('User created'), __('An invitation email was sent.'));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.users.index', [
            'rows' => $this->api()->endpoint('users')->page($this->activeFilters(), $this->tableQuery()),
            'roleOptions' => app(Lookups::class)->roles(),
            'roleNames' => app(Lookups::class)->roleNames(),
            'chips' => $this->filterChips([
                'status' => ['_name' => __('Membership'), 'active' => __('Active'), 'inactive' => __('Inactive')],
                'role' => ['_name' => __('Role')] + app(Lookups::class)->roleNames(),
                'locked' => ['_name' => __('Locked'), '1' => __('Yes'), '0' => __('No')],
            ]),
        ]);
    }
}
