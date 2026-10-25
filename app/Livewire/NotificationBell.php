<?php

namespace App\Livewire;

use App\Api\CampusApi;
use App\Api\Exceptions\ApiException;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Bell in the top bar with the unread count (refreshes every minute).
 */
class NotificationBell extends Component
{
    public int $unread = 0;

    public function mount(CampusApi $api): void
    {
        $this->refreshCount($api);
    }

    #[On('notifications-changed')]
    public function refreshCount(CampusApi $api): void
    {
        try {
            $this->unread = $api->withoutCache(fn () => $api->me()->unreadNotifications());
        } catch (ApiException) {
            // The bell must never break the page; keep the last known count.
        }
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
