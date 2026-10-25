<div>
    <x-ui.page-header :title="__('Notifications')" :description="__('Messages about your account, approvals and institution updates.')">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="check" wire:click="markAllRead" loading="markAllRead">{{ __('Mark all as read') }}</x-ui.button>
            <x-ui.button variant="ghost" icon="settings" :href="route('account.notifications')">{{ __('Preferences') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4">
        <x-ui.button-group>
            <x-ui.button size="sm" :variant="$unreadOnly ? 'secondary' : 'soft'" wire:click="$set('unreadOnly', false)">{{ __('All') }}</x-ui.button>
            <x-ui.button size="sm" :variant="$unreadOnly ? 'soft' : 'secondary'" wire:click="$set('unreadOnly', true)">{{ __('Unread') }}</x-ui.button>
        </x-ui.button-group>
    </div>

    <x-ui.card :padding="false">
        <div wire:loading.class="opacity-60" wire:target="unreadOnly,loadMore,markRead">
            @forelse ($items as $notification)
                <div wire:key="n-{{ $notification['id'] }}" @class(['flex gap-4 border-b border-line px-5 py-4 last:border-0', 'bg-primary-soft/30' => ! $notification['read_at']])>
                    <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-primary' => ! $notification['read_at']])></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                            <p class="text-sm font-medium text-ink">{{ $notification['title'] }}</p>
                            <x-ui.time :value="$notification['created_at']" class="text-xs text-ink-subtle" />
                        </div>
                        <p class="mt-1 text-sm text-ink-muted">{{ $notification['body'] }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            @if ($notification['category'])
                                <x-ui.badge size="sm">{{ \App\Support\Format::label($notification['category']) }}</x-ui.badge>
                            @endif
                            @if ($notification['action_url'])
                                <a href="{{ $notification['action_url'] }}" class="text-sm font-medium text-primary hover:underline">{{ $notification['action_text'] ?? __('Open') }}</a>
                            @endif
                            @unless ($notification['read_at'])
                                <button type="button" wire:click="markRead('{{ $notification['id'] }}')" class="text-sm text-ink-muted hover:text-ink">{{ __('Mark as read') }}</button>
                            @endunless
                        </div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state icon="inbox" :title="$unreadOnly ? __('No unread notifications') : __('No notifications yet')" :description="__('You will see sign-in alerts, approvals and announcements here.')" />
            @endforelse
        </div>

        @if ($nextCursor)
            <x-slot:footer class="justify-center!">
                <x-ui.button variant="ghost" wire:click="loadMore('{{ $nextCursor }}')" loading="loadMore">{{ __('Load more') }}</x-ui.button>
            </x-slot:footer>
        @endif
    </x-ui.card>
</div>
