<div>
    <x-ui.page-header :title="__('Announcements')" :description="__('Circulars and notices sent to you.')">
        <x-slot:actions>
            @canapi('communication.announcements.publish', 'communication.announcements.publish_all')
                <x-ui.button variant="secondary" icon="megaphone" :href="route('announcements.manage')">{{ __('Post announcements') }}</x-ui.button>
            @endcanapi
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4"><x-ui.switch wire:model.live="unread" :label="__('Unread only')" /></div>

    <x-ui.card :padding="false">
        @forelse ($items as $a)
            <a wire:key="an-{{ $a['id'] }}" href="{{ route('announcements.show', $a['id']) }}" wire:navigate @class(['flex gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-surface-muted', 'bg-primary-soft/30' => ! $a['read_at']])>
                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-primary' => ! $a['read_at'], 'bg-transparent' => $a['read_at']])></span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        @if ($a['is_pinned'])<x-ui.icon name="pin" class="size-3.5 text-ink-muted" />@endif
                        <span class="font-medium text-ink">{{ $a['title'] }}</span>
                        @if ($a['priority'] === 'important')<x-ui.badge size="sm" tone="danger">{{ __('Important') }}</x-ui.badge>@endif
                        @if ($a['requires_ack'] && ! $a['acknowledged_at'])<x-ui.badge size="sm" tone="warning">{{ __('Please acknowledge') }}</x-ui.badge>@endif
                    </span>
                    <span class="mt-0.5 line-clamp-2 block text-ink-muted">{{ $a['body'] }}</span>
                    <span class="mt-0.5 block text-xs text-ink-subtle">{{ $a['from'] }} · {{ \App\Support\Format::dateTime($a['published_at']) }}</span>
                </span>
            </a>
        @empty
            <x-ui.empty-state compact icon="megaphone" :title="__('No announcements')" />
        @endforelse
    </x-ui.card>
</div>
