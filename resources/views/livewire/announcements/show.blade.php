<div>
    <x-ui.page-header :title="$a['title']" :description="($a['from'] ?? '').($a['published_at'] ? ' · '.\App\Support\Format::dateTime($a['published_at']) : '')" :back="route('announcements.index')" :back-label="__('Announcements')">
        <x-slot:meta>
            @if ($a['priority'] === 'important')<x-ui.badge size="sm" tone="danger">{{ __('Important') }}</x-ui.badge>@endif
            @isset($a['status'])<x-ui.status-badge :status="$a['status']" size="sm" />@endisset
            @if ($a['expires_on'])<span class="text-xs text-ink-muted">{{ __('until :d', ['d' => \App\Support\Format::date($a['expires_on'])]) }}</span>@endif
        </x-slot:meta>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <div class="whitespace-pre-line text-sm leading-relaxed text-ink">{{ $a['body'] }}</div>
            <x-attachments :files="$a['attachments'] ?? []" class="mt-4" />
            @if ($a['requires_ack'] && $a['read_at'] !== null)
                <div class="mt-6 border-t border-line pt-4">
                    @if ($a['acknowledged_at'])
                        <p class="text-sm text-success">{{ __('You acknowledged this on :d.', ['d' => \App\Support\Format::dateTime($a['acknowledged_at'])]) }}</p>
                    @else
                        <x-ui.button icon="check" wire:click="acknowledge" loading="acknowledge">{{ __('I have read and understood this') }}</x-ui.button>
                    @endif
                </div>
            @endif
        </x-ui.card>

        @if ($stats)
            <x-ui.card :title="__('Who has seen it')">
                <p class="text-sm text-ink">{{ __(':r of :t read', ['r' => $stats['read'], 't' => $stats['recipients']]) }}@if ($a['requires_ack']) · {{ __(':a acknowledged', ['a' => $stats['acknowledged']]) }}@endif</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('Sent to: :a', ['a' => $a['audience']]) }}</p>
                @if ($stats['pending'] !== [])
                    <p class="mt-4 text-xs font-medium uppercase text-ink-subtle">{{ $a['requires_ack'] ? __('Not acknowledged yet') : __('Not read yet') }}</p>
                    <ul class="mt-1 max-h-80 space-y-1 overflow-y-auto text-sm">
                        @foreach ($stats['pending'] as $p)<li wire:key="pp-{{ $loop->index }}" class="text-ink-muted">{{ $p['name'] }}</li>@endforeach
                    </ul>
                @endif
            </x-ui.card>
        @endif
    </div>
</div>
