@php
    $hour = (int) now()->setTimezone($currentUser?->timezone() ?? 'Asia/Kolkata')->format('G');
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
@endphp
<div>
    <x-ui.page-header :title="$greeting.', '.strtok((string) $currentUser?->name(), ' ')"
        :description="$term ? __(':term · :year', ['term' => $term['name'], 'year' => $term['academic_year']['name'] ?? '']) : ($currentUser?->institution()['name'] ?? null)" />

    @if ($currentUser && ! $currentUser->get('mfa.enabled'))
        <x-ui.alert tone="warning" :title="__('Protect your account with two-step verification')" class="mb-6">
            {{ __('It takes a minute and stops others signing in even if they know your password.') }}
            <x-slot:actions><x-ui.button size="sm" variant="secondary" :href="route('account.security')">{{ __('Set it up') }}</x-ui.button></x-slot:actions>
        </x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card :title="__('Latest notifications')" :padding="false">
                <x-slot:actions><x-ui.button size="sm" variant="ghost" :href="route('notifications.index')" icon-right="arrow-right">{{ __('View all') }}</x-ui.button></x-slot:actions>
                @forelse ($notifications as $notification)
                    <div wire:key="n-{{ $notification['id'] }}" @class(['flex gap-3 border-b border-line px-5 py-3.5 last:border-0', 'bg-primary-soft/30' => ! $notification['read_at']])>
                        <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-primary' => ! $notification['read_at'], 'bg-transparent' => $notification['read_at']])></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ $notification['title'] }}</p>
                            <p class="mt-0.5 line-clamp-2 text-sm text-ink-muted">{{ $notification['body'] }}</p>
                        </div>
                        <x-ui.time :value="$notification['created_at']" class="shrink-0 text-xs text-ink-subtle" />
                    </div>
                @empty
                    <x-ui.empty-state compact icon="inbox" :title="__('You are all caught up')" :description="__('New messages about your account and work appear here.')" />
                @endforelse
            </x-ui.card>

            @if ($shortcuts !== [])
                <x-ui.card :title="__('Go to')">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($shortcuts as $shortcut)
                            <a href="{{ $shortcut['url'] }}" wire:key="s-{{ $loop->index }}" class="flex items-center justify-between rounded-lg border border-line px-4 py-3 text-sm transition hover:border-primary/50 hover:bg-primary-soft/30">
                                <span>
                                    <span class="block font-medium text-ink">{{ $shortcut['label'] }}</span>
                                    <span class="block text-xs text-ink-muted">{{ $shortcut['section'] }}</span>
                                </span>
                                <x-ui.icon name="chevron-right" class="size-4 text-ink-subtle" />
                            </a>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif
        </div>

        <x-ui.card :title="__('Coming up')" :padding="false">
            <x-slot:actions><x-ui.button size="sm" variant="ghost" :href="route('calendar.index')" icon-right="arrow-right">{{ __('Calendar') }}</x-ui.button></x-slot:actions>
            @forelse ($events as $event)
                <div wire:key="e-{{ $event['id'] }}" class="flex gap-3 border-b border-line px-5 py-3 last:border-0">
                    <div class="w-11 shrink-0 rounded-md border border-line text-center">
                        <p class="bg-surface-muted text-[10px] font-semibold uppercase text-ink-muted">{{ \App\Support\Format::date($event['starts_on'], 'M') }}</p>
                        <p class="tabular py-0.5 text-base font-semibold text-ink">{{ \App\Support\Format::date($event['starts_on'], 'j') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink">{{ $event['title'] }}</p>
                        <p class="text-xs text-ink-muted">
                            {{ \App\Support\Format::label($event['type']) }}
                            @if ($event['ends_on'] !== $event['starts_on']) · {{ __('until :date', ['date' => \App\Support\Format::date($event['ends_on'], 'd M')]) }} @endif
                        </p>
                    </div>
                    @if ($event['is_holiday'])<x-ui.badge tone="success" size="sm" class="ml-auto self-start">{{ __('Holiday') }}</x-ui.badge>@endif
                </div>
            @empty
                <x-ui.empty-state compact icon="calendar" :title="__('Nothing in the next 30 days')" />
            @endforelse
        </x-ui.card>
    </div>
</div>
