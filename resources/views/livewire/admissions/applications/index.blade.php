<div>
    <x-ui.page-header :title="__('Applications')" :description="__('Submitted applications, oldest first. Open one to verify documents and decide.')" />

    @if ($summary !== [])
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (['submitted', 'correction_requested', 'eligible', 'approved', 'offered', 'offer_accepted', 'admitted'] as $status)
                <button type="button" wire:click="$set('filters.status', '{{ $status }}')" wire:key="sum-{{ $status }}"
                    class="rounded-full border border-line bg-surface px-3 py-1 text-sm hover:border-primary/40">
                    {{ $statuses[$status] ?? $status }} <span class="font-semibold text-ink">{{ $summary[$status] ?? 0 }}</span>
                </button>
            @endforeach
        </div>
    @endif

    <x-ui.filters search="search" :search-placeholder="__('Name, email or application number…')" :active="$chips">
        <x-ui.select wire:model.live="filters.cycle" :options="$cycles" :placeholder="__('All cycles')" class="sm:w-48" :aria-label="__('Cycle')" />
        <x-ui.select wire:model.live="filters.programme" :options="$programmes" :placeholder="__('All programmes')" class="sm:w-52" :aria-label="__('Programme')" />
        <x-ui.select wire:model.live="filters.quota" :options="$quotas" :placeholder="__('Any quota')" class="sm:w-40" :aria-label="__('Quota')" />
        <x-ui.select wire:model.live="filters.status" :options="$statuses" :placeholder="__('Any status')" class="sm:w-44" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Applicant') }}</x-ui.th>
            <x-ui.th>{{ __('Programme') }}</x-ui.th>
            <x-ui.th>{{ __('Quota') }}</x-ui.th>
            <x-ui.th>{{ __('Marks %') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th>{{ __('Submitted') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $application)
            <x-ui.tr wire:key="ap-{{ $application['id'] }}" :href="route('applications.show', $application['id'])">
                <x-ui.td primary>
                    <a href="{{ route('applications.show', $application['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $application['applicant']['name'] }}</a>
                    <p class="font-mono text-xs text-ink-muted">{{ $application['application_no'] ?? __('Draft') }}</p>
                </x-ui.td>
                <x-ui.td :label="__('Programme')">{{ $application['seat']['programme']['name'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Quota')">{{ $quotas[$application['seat']['quota']] ?? $application['seat']['quota'] }}</x-ui.td>
                <x-ui.td :label="__('Marks %')">{{ $application['qualifying_percentage'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$application['status']" :label="$application['status_label']" /></x-ui.td>
                <x-ui.td :label="__('Submitted')"><x-ui.time :value="$application['submitted_at']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="inbox" :title="__('No applications found')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>
</div>
