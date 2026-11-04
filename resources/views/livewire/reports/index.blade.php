<div>
    <x-ui.page-header :title="__('Reports')" :description="__('Standard reports you can view and download as CSV.')" />

    @forelse ($groups as $module => $reports)
        <h2 wire:key="rg-{{ $module }}" class="mb-2 mt-6 text-xs font-semibold uppercase tracking-wide text-ink-subtle first:mt-0">{{ $module }}</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($reports as $r)
                <a wire:key="rp-{{ $r['key'] }}" href="{{ route('reports.show', $r['key']) }}" wire:navigate class="rounded-lg border border-line bg-surface px-4 py-3 transition hover:border-primary/50 hover:bg-primary-soft/30">
                    <span class="block font-medium text-ink">{{ $r['label'] }}</span>
                    <span class="mt-0.5 block text-sm text-ink-muted">{{ $r['description'] }}</span>
                </a>
            @endforeach
        </div>
    @empty
        <x-ui.empty-state icon="file-chart-column" :title="__('No reports for your role')" />
    @endforelse
</div>
