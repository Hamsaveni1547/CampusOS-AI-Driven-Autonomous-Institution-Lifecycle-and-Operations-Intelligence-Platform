@php($tones = ['ok' => 'success', 'warn' => 'warning', 'degraded' => 'warning', 'fail' => 'danger'])
@php($labels = ['ok' => __('Healthy'), 'warn' => __('Warning'), 'degraded' => __('Degraded'), 'fail' => __('Failing')])
<div wire:poll.30s>
    <x-ui.page-header :title="__('Monitoring')" :description="__('Health of the whole platform. Refreshes every 30 seconds.')">
        <x-slot:meta>
            <x-ui.badge :tone="$tones[$health['status'] ?? 'fail'] ?? 'neutral'" dot>{{ $labels[$health['status'] ?? 'fail'] ?? $health['status'] }}</x-ui.badge>
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="refresh-cw" wire:click="$refresh">{{ __('Check now') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($health['checks'] ?? [] as $name => $check)
            <x-ui.card wire:key="h-{{ $name }}">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-semibold text-ink">{{ \App\Support\Format::label($name) }}</p>
                    <x-ui.badge size="sm" :tone="$tones[$check['status']] ?? 'neutral'" dot>{{ $labels[$check['status']] ?? $check['status'] }}</x-ui.badge>
                </div>
                @if (isset($check['latency_ms']))<p class="mt-1 text-xs text-ink-muted">{{ __(':ms ms', ['ms' => $check['latency_ms']]) }}</p>@endif
                @if (! empty($check['message']))<p class="mt-2 text-xs text-ink-muted">{{ $check['message'] }}</p>@endif
            </x-ui.card>
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($queues as $queue)
            <x-ui.stat wire:key="q-{{ $queue['queue'] }}" icon="layers" :label="__('Queue: :name', ['name' => $queue['queue']])" :value="\App\Support\Format::number($queue['pending'])" :hint="__('waiting jobs')" :tone="$queue['pending'] > 500 ? 'warning' : 'primary'" />
        @endforeach
    </div>

    <x-ui.card class="mt-6" :title="__('Failed jobs')" :description="trans_choice(':count job|:count jobs', count($failed))" :padding="false">
        @forelse ($failed as $job)
            <div wire:key="fj-{{ $job['id'] }}" class="flex flex-wrap items-start gap-3 border-b border-line px-5 py-3 last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-ink">{{ class_basename(str_replace('\\', '/', (string) $job['job'])) ?: __('Unknown job') }} <span class="font-normal text-ink-muted">· {{ $job['queue'] }}</span></p>
                    <p class="mt-0.5 break-all text-xs text-danger">{{ $job['exception'] }}</p>
                    <p class="mt-0.5 text-xs text-ink-subtle">{{ \App\Support\Format::dateTime($job['failed_at']) }}</p>
                </div>
                <x-ui.button size="sm" variant="secondary" icon="rotate-ccw" wire:click="retry('{{ $job['id'] }}')">{{ __('Retry') }}</x-ui.button>
                <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="forget('{{ $job['id'] }}')" wire:confirm="{{ __('Remove this failed job?') }}" :aria-label="__('Remove')" />
            </div>
        @empty
            <x-ui.empty-state compact icon="circle-check" :title="__('No failed jobs')" />
        @endforelse
    </x-ui.card>

    <p class="mt-4 text-xs text-ink-subtle">{{ __('Last checked') }}: <x-ui.time :value="$checkedAt" /></p>
</div>
