<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * In-app notifications: all / unread, mark as read, load more.
 */
#[Layout('layouts::app')]
#[Title('Notifications')]
class Notifications extends Component
{
    use InteractsWithApi;

    #[Url(except: false)]
    public bool $unreadOnly = false;

    /**
     * Cursors of the pages loaded so far ("" = first page).
     *
     * @var array<int, string>
     */
    public array $cursors = [''];

    public function updatedUnreadOnly(): void
    {
        $this->cursors = [''];
    }

    public function loadMore(string $cursor): void
    {
        $this->cursors[] = $cursor;
    }

    public function markRead(string $id): void
    {
        $this->callApi(fn () => $this->api()->me()->markNotificationRead($id), onSuccess: fn () => $this->dispatch('notifications-changed'));
    }

    public function markAllRead(): void
    {
        $this->callApi(fn () => $this->api()->me()->markAllNotificationsRead(), onSuccess: function (): void {
            $this->dispatch('notifications-changed');
            $this->toast('success', __('All notifications marked as read'));
        });
    }

    public function render(): View
    {
        $filters = $this->unreadOnly ? ['unread' => 1] : [];
        $items = [];
        $nextCursor = null;

        foreach ($this->cursors as $cursor) {
            $page = $this->api()->me()->notifications($filters, $cursor === '' ? null : $cursor);
            $items = array_merge($items, $page->items);
            $nextCursor = $page->nextCursor;
        }

        return view('livewire.notifications', ['items' => $items, 'nextCursor' => $nextCursor]);
    }
}
