<?php

namespace App\Livewire\Account;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Which messages arrive in the app and by email. Security and account messages are always sent.
 */
#[Layout('layouts::app')]
#[Title('Notification settings')]
class NotificationPreferences extends Component
{
    use InteractsWithApi;

    /**
     * category => [channel => enabled]
     *
     * @var array<string, array<string, bool>>
     */
    public array $preferences = [];

    /**
     * @var array<int, array{category: string, label: string, mandatory: bool}>
     */
    public array $rows = [];

    public function mount(): void
    {
        $this->load($this->api()->me()->notificationPreferences());
    }

    public function save(): void
    {
        $editable = collect($this->rows)->reject(fn (array $row): bool => $row['mandatory'])->pluck('category')->all();
        $changes = array_intersect_key($this->preferences, array_flip($editable));

        $this->callApi(fn () => $this->api()->me()->updateNotificationPreferences($changes), onSuccess: function (array $matrix): void {
            $this->load($matrix);
            $this->toast('success', __('Notification settings saved'));
        });
    }

    public function render(): View
    {
        return view('livewire.account.notification-preferences');
    }

    /**
     * @param  array<int, array<string, mixed>>  $matrix
     */
    private function load(array $matrix): void
    {
        $this->rows = array_map(fn (array $row): array => ['category' => $row['category'], 'label' => $row['label'], 'mandatory' => (bool) $row['mandatory']], $matrix);
        $this->preferences = collect($matrix)->mapWithKeys(fn (array $row): array => [$row['category'] => array_map('boolval', (array) $row['channels'])])->all();
    }
}
