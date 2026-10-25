@php($tones = ['ok' => 'success', 'warn' => 'warning', 'degraded' => 'warning', 'fail' => 'danger'])
@php($labels = ['ok' => __('Healthy'), 'warn' => __('Warning'), 'degraded' => __('Degraded'), 'fail' => __('Failing')])
<div wire:poll.30s>
    <x-ui.page-header :title="__('System health')" :description="__('Checked every 30 seconds while this page is open.')">
        <x-slot:meta>
            <x-ui.badge :tone="$tones[$health['status'] ?? 'fail'] ?? 'neutral'" dot>{{ $labels[$health['status'] ?? 'fail'] ?? $health['status'] }}</x-ui.badge>
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="refresh-cw" wire:click="$refresh">{{ __('Check now') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($health['checks'] ?? [] as $name => $check)
            <x-ui.card wire:key="h-{{ $name }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-ink">{{ \App\Support\Format::label($name) }}</p>
                        @if (isset($check['latency_ms']))
                            <p class="mt-1 text-xs text-ink-muted">{{ __(':ms ms', ['ms' => $check['latency_ms']]) }}</p>
                        @endif
                    </div>
                    <x-ui.badge size="sm" :tone="$tones[$check['status']] ?? 'neutral'" dot>{{ $labels[$check['status']] ?? $check['status'] }}</x-ui.badge>
                </div>
                @if (! empty($check['message']))
                    <p class="mt-3 text-sm text-ink-muted">{{ $check['message'] }}</p>
                @endif
            </x-ui.card>
        @endforeach
    </div>
    <p class="mt-4 text-xs text-ink-subtle">{{ __('Last checked') }}: <x-ui.time :value="$checkedAt" /></p>
</div>
