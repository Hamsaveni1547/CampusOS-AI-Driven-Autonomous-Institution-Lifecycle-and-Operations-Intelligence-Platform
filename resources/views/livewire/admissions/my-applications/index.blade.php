<div>
    <x-ui.page-header :title="__('My applications')" :description="__('Apply for admission and follow your application here.')" />

    @if ($formError)<x-ui.alert class="mb-6" tone="danger">{{ $formError }}</x-ui.alert>@endif

    @if ($applications !== [])
        <div class="mb-8 grid gap-4 md:grid-cols-2">
            @foreach ($applications as $application)
                <a wire:key="mine-{{ $application['id'] }}" href="{{ route('my-applications.show', $application['id']) }}" wire:navigate
                    class="rounded-lg border border-line bg-surface p-5 shadow-sm transition hover:border-primary/40 dark:shadow-none">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink">{{ $application['seat']['programme']['name'] ?? '' }}</p>
                            <p class="text-sm text-ink-muted">{{ $application['cycle']['name'] }} · {{ $quotas[$application['seat']['quota']] ?? $application['seat']['quota'] }}</p>
                        </div>
                        <x-ui.status-badge :status="$application['status']" :label="$application['status_label']" />
                    </div>
                    <p class="mt-3 font-mono text-xs text-ink-subtle">{{ $application['application_no'] ?? __('Not submitted yet') }}</p>
                    @if ($application['status'] === 'offered')
                        <p class="mt-2 text-sm font-medium text-success">{{ __('You have an offer. Accept it before :date.', ['date' => \App\Support\Format::date($application['offer_expires_at'])]) }}</p>
                    @elseif ($application['status'] === 'correction_requested')
                        <p class="mt-2 text-sm font-medium text-warning">{{ __('Corrections requested. Open to see what to fix.') }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    <h2 class="mb-3 text-base font-semibold text-ink">{{ __('Open for applications') }}</h2>
    @forelse ($cycles as $cycle)
        <x-ui.card wire:key="open-{{ $cycle['id'] }}" class="mb-4" :title="$cycle['name']" :description="__('Apply by :date', ['date' => \App\Support\Format::date($cycle['closes_on'])]).($cycle['application_fee'] > 0 ? ' · '.__('Application fee ₹:fee', ['fee' => \App\Support\Format::number($cycle['application_fee'])]) : '')" :padding="false">
            @if ($cycle['instructions'])<p class="whitespace-pre-line border-b border-line px-5 py-3 text-sm text-ink-muted">{{ $cycle['instructions'] }}</p>@endif
            @foreach ($cycle['seats'] as $seat)
                <div wire:key="os-{{ $seat['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-ink">{{ $seat['programme']['name'] ?? '' }}</p>
                        <p class="text-xs text-ink-muted">
                            {{ $quotas[$seat['quota']] ?? $seat['quota'] }}
                            @if ($seat['min_percentage']) · {{ __('Minimum :p%', ['p' => $seat['min_percentage']]) }}@endif
                            · {{ trans_choice(':count seat left|:count seats left', $seat['available']) }}
                        </p>
                    </div>
                    <x-ui.button size="sm" wire:click="apply('{{ $seat['id'] }}')" loading="apply" :disabled="$seat['available'] === 0">{{ __('Apply') }}</x-ui.button>
                </div>
            @endforeach
        </x-ui.card>
    @empty
        <x-ui.empty-state icon="calendar-range" :title="__('No admissions are open right now')" :description="__('Check back when the next admission cycle opens.')" />
    @endforelse
</div>
