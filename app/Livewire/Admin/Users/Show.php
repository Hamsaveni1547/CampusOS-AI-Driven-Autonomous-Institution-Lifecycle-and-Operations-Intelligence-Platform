<?php

namespace App\Livewire\Admin\Users;

use App\Api\Resources\Endpoint;
use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One user: details, roles, security actions and sign-in history.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $userId = '';

    /** @var array<string, mixed> */
    public array $form = ['name' => '', 'email' => '', 'mobile' => '', 'username' => ''];

    /** @var array<string, mixed> */
    public array $assign = ['role_id' => '', 'department_id' => '', 'valid_until' => '', 'reason' => ''];

    public string $reason = '';

    public ?string $revokeId = null;

    public ?string $temporaryPassword = null;

    public function mount(string $user): void
    {
        $this->userId = $user;
    }

    private function users(): Endpoint
    {
        return $this->api()->endpoint('users');
    }

    public function edit(): void
    {
        $user = $this->users()->find($this->userId);
        $this->form = ['name' => $user['name'], 'email' => $user['email'], 'mobile' => (string) $user['mobile'], 'username' => (string) $user['username']];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'edit-user');
    }

    public function save(): void
    {
        $this->validate(['form.name' => 'required|string|max:150', 'form.email' => 'required|email']);

        $this->callApi(fn () => $this->users()->update($this->userId, [
            'name' => $this->form['name'],
            'email' => $this->form['email'],
            'mobile' => $this->form['mobile'] ?: null,
            'username' => $this->form['username'] ?: null,
        ]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'edit-user');
            $this->toast('success', __('User updated'));
        });
    }

    public function assignRole(): void
    {
        $this->validate(['assign.role_id' => 'required|string']);

        $this->callApi(fn () => $this->users()->nested($this->userId, 'role-assignments')->create(array_filter([
            'role_id' => $this->assign['role_id'],
            'department_id' => $this->assign['department_id'] ?: null,
            'valid_until' => $this->assign['valid_until'] ?: null,
            'reason' => $this->assign['reason'] ?: null,
        ])), fieldMap: ['role_id' => 'assign.role_id', 'department_id' => 'assign.department_id', 'valid_until' => 'assign.valid_until'], onSuccess: function (): void {
            $this->reset('assign');
            $this->dispatch('close-dialog', 'assign-role');
            $this->toast('success', __('Role assigned'));
        });
    }

    public function askRevoke(string $assignmentId): void
    {
        $this->revokeId = $assignmentId;
        $this->dispatch('open-dialog', 'revoke-role');
    }

    public function revoke(): void
    {
        if ($this->revokeId === null) {
            return;
        }

        $this->callApi(fn () => $this->api()->endpoint('role-assignments')->delete($this->revokeId, ['reason' => $this->reason ?: null]), onSuccess: function (): void {
            $this->reset('revokeId', 'reason');
            $this->dispatch('close-dialog', 'revoke-role');
            $this->toast('success', __('Role revoked'));
        });
    }

    public function deactivate(): void
    {
        $this->validate(['reason' => 'required|string|max:255']);

        $this->callApi(fn () => $this->users()->action($this->userId, 'deactivate', ['reason' => $this->reason]), onSuccess: function (): void {
            $this->reason = '';
            $this->dispatch('close-dialog', 'deactivate-user');
            $this->toast('success', __('User deactivated'));
        });
    }

    public function activate(): void
    {
        $this->callApi(fn () => $this->users()->action($this->userId, 'activate'), onSuccess: fn () => $this->toast('success', __('User re-activated')));
    }

    public function unlock(): void
    {
        $this->callApi(fn () => $this->users()->action($this->userId, 'unlock'), onSuccess: fn () => $this->toast('success', __('Account unlocked')));
    }

    public function resetPassword(string $mode): void
    {
        $response = $this->callApi(fn () => $this->users()->action($this->userId, 'reset-password', ['mode' => $mode === 'temporary_password' ? 'temporary_password' : 'link']));

        if ($response !== null) {
            $this->temporaryPassword = $response->data('temporary_password');
            $this->dispatch('close-dialog', 'reset-password');

            $this->temporaryPassword
                ? $this->dispatch('open-dialog', 'temporary-password')
                : $this->toast('success', (string) $response->message());
        }
    }

    public function resetMfa(): void
    {
        $this->callApi(fn () => $this->users()->action($this->userId, 'reset-mfa'), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'reset-mfa');
            $this->toast('success', __('Two-step verification reset'));
        });
    }

    public function resendInvitation(): void
    {
        $this->callApi(fn () => $this->users()->action($this->userId, 'resend-invitation'), onSuccess: fn () => $this->toast('success', __('Invitation sent again')));
    }

    public function render(): View
    {
        $user = $this->users()->find($this->userId);
        $events = $this->can('identity.security_events.view')
            ? array_slice($this->api()->get('users/'.$this->userId.'/security-events')->page()->items, 0, 10)
            : [];
        $lookups = app(Lookups::class);
        $canAssign = $this->can('identity.role_assignments.manage');

        return view('livewire.admin.users.show', [
            'user' => $user,
            'events' => $events,
            'roleOptions' => $canAssign ? $lookups->roles() : [],
            'departments' => $canAssign ? $lookups->departments() : [],
        ])->title($user['name']);
    }
}
