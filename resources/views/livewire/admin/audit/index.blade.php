<div x-data="{ detail: null }">
    <x-ui.page-header :title="__('Audit log')" :description="__('A permanent record of changes and security events in this institution.')">
        <x-slot:actions>
            @if ($exportUrl)
                <x-ui.button variant="secondary" icon="download" :href="$exportUrl">{{ __('Export CSV') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($canActivities && $canSecurity)
        <div class="mb-6 flex gap-5 border-b border-line">
            <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'activities')" :active="$tab === 'activities'">{{ __('Changes') }}</x-ui.tab-link>
            <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'security')" :active="$tab === 'security'">{{ __('Security events') }}</x-ui.tab-link>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-end gap-3">
        @if ($tab === 'activities')
            <x-ui.select wire:model.live="filters.log" :options="['default' => __('Records'), 'admin' => __('Administration'), 'identity' => __('Accounts')]" :placeholder="__('Any area')" class="sm:w-44" :aria-label="__('Area')" />
            <x-ui.select wire:model.live="filters.event" :options="['created' => __('Created'), 'updated' => __('Updated'), 'deleted' => __('Deleted')]" :placeholder="__('Any change')" class="sm:w-40" :aria-label="__('Change')" />
        @else
            <x-ui.select wire:model.live="filters.severity" :options="['info' => __('Info'), 'warning' => __('Warning'), 'critical' => __('Critical')]" :placeholder="__('Any severity')" class="sm:w-40" :aria-label="__('Severity')" />
            <x-ui.input wire:model.live.debounce.500ms="filters.event" :placeholder="__('Event, e.g. login_failed')" class="sm:w-56" :aria-label="__('Event')" />
        @endif
        <x-ui.date-picker wire:model.live="filters.from" :aria-label="__('From')" :placeholder="__('From')" class="sm:w-40" />
        <x-ui.date-picker wire:model.live="filters.to" :aria-label="__('To')" :placeholder="__('To')" class="sm:w-40" />
        @if (array_filter($filters))
            <x-ui.button variant="ghost" size="sm" wire:click="resetFilters">{{ __('Clear') }}</x-ui.button>
        @endif
    </div>

    @if ($page)
        <x-ui.table :empty="$page->isEmpty()" loading="filters,tab,nextPage,previousPage">
            <x-slot:head>
                <x-ui.th>{{ __('What') }}</x-ui.th>
                <x-ui.th>{{ __('Who') }}</x-ui.th>
                <x-ui.th>{{ __('From') }}</x-ui.th>
                <x-ui.th>{{ __('When') }}</x-ui.th>
            </x-slot:head>
            @foreach ($page->items as $entry)
                <x-ui.tr wire:key="a-{{ $tab }}-{{ $entry['id'] }}">
                    <x-ui.td primary>
                        <button type="button" x-on:click="detail = @js($entry); $dispatch('open-dialog', 'audit-detail')" class="text-left hover:underline">
                            @if ($tab === 'activities')
                                <span class="font-medium text-ink">{{ $entry['description'] }}</span>
                                @if ($entry['subject'])<span class="block text-xs text-ink-muted">{{ \App\Support\Format::label($entry['subject']['type']) }} #{{ $entry['subject']['id'] }}</span>@endif
                            @else
                                <span class="font-medium text-ink">{{ \App\Support\Format::label($entry['event']) }}</span>
                                <x-ui.badge size="sm" :tone="$entry['severity'] === 'critical' ? 'danger' : ($entry['severity'] === 'warning' ? 'warning' : 'neutral')">{{ \App\Support\Format::label($entry['severity']) }}</x-ui.badge>
                            @endif
                        </button>
                    </x-ui.td>
                    <x-ui.td :label="__('Who')">
                        @if ($tab === 'activities')
                            {{ $entry['causer']['name'] ?? __('System') }}
                        @else
                            {{ $entry['user']['name'] ?? ($entry['actor']['name'] ?? '—') }}
                        @endif
                    </x-ui.td>
                    <x-ui.td :label="__('From')"><span class="font-mono text-xs">{{ $entry['ip_address'] ?? '—' }}</span></x-ui.td>
                    <x-ui.td :label="__('When')"><x-ui.time :value="$entry['created_at']" /></x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="scroll-text" :title="__('Nothing recorded for these filters')" /></x-slot:emptyState>
            <x-slot:footer>
                <div class="flex justify-end gap-2 px-4 py-3">
                    <x-ui.button size="sm" variant="secondary" wire:click="previousPage" :disabled="$previousCursors === []">{{ __('Newer') }}</x-ui.button>
                    <x-ui.button size="sm" variant="secondary" wire:click="nextPage('{{ $page->nextCursor }}')" :disabled="$page->nextCursor === null">{{ __('Older') }}</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.table>
    @endif

    <x-ui.drawer name="audit-detail" size="lg" :title="__('Details')">
        <pre x-show="detail" x-text="JSON.stringify(detail, null, 2)" class="max-h-[70vh] overflow-auto whitespace-pre-wrap break-all rounded-lg bg-surface-muted p-4 text-xs text-ink"></pre>
    </x-ui.drawer>
</div>
