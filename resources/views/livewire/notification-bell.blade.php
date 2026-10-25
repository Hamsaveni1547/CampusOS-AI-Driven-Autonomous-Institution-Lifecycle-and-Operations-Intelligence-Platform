<a href="{{ route('notifications.index') }}" wire:poll.60s.visible="refreshCount"
    class="relative rounded-md p-2 text-ink-muted transition hover:bg-surface-muted hover:text-ink"
    aria-label="{{ $unread > 0 ? __('Notifications (:count unread)', ['count' => $unread]) : __('Notifications') }}">
    <x-ui.icon name="bell" class="size-5" />
    @if ($unread > 0)
        <span class="tabular absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-semibold leading-none text-white">
            {{ $unread > 99 ? '99+' : $unread }}
        </span>
    @endif
</a>
