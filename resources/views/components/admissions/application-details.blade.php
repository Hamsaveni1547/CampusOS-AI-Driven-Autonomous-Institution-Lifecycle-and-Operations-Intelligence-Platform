{{--
    Read-only application details shared by the admissions desk and the applicant.
    <x-admissions.application-details :application="$application" :quotas="$quotas" :categories="$categories" />
    Slot "documentActions" receives nothing; use the $document variable inside with @scope-like loops in the caller instead.
--}}
@props(['application', 'quotas' => [], 'categories' => [], 'reviewable' => false])
@php
    $personal = $application['personal'] ?? [];
    $guardian = $application['guardian'] ?? [];
    $academic = $application['academic'] ?? [];
    $entrance = $application['entrance'] ?? [];
    $uploaded = collect($application['documents'] ?? [])->keyBy('document_type');
@endphp
<div class="space-y-6">
    <x-ui.card :title="__('Programme')">
        <x-ui.description-list :items="[
            __('Programme') => $application['seat']['programme']['name'] ?? null,
            __('Quota') => $quotas[$application['seat']['quota']] ?? \App\Support\Format::label($application['seat']['quota']),
            __('Category') => $application['category'] ? ($categories[$application['category']] ?? $application['category']) : null,
            __('Cycle') => $application['cycle']['name'],
        ]" />
    </x-ui.card>

    <x-ui.card :title="__('Applicant')">
        <x-ui.description-list :items="[
            __('Name') => $personal['full_name'] ?? $application['applicant']['name'],
            __('Email') => $application['applicant']['email'],
            __('Mobile') => $application['applicant']['mobile'],
            __('Date of birth') => isset($personal['date_of_birth']) ? \App\Support\Format::date($personal['date_of_birth']) : null,
            __('Gender') => isset($personal['gender']) ? \App\Support\Format::label($personal['gender']) : null,
            __('Address') => $personal['address'] ?? null,
            __('Parent / guardian') => trim(($guardian['name'] ?? '').' '.($guardian['phone'] ?? '')) ?: null,
        ]" />
    </x-ui.card>

    <x-ui.card :title="__('Academic record')">
        <x-ui.description-list :items="[
            __('Qualifying exam %') => $application['qualifying_percentage'],
            __('Board / university') => $academic['board'] ?? null,
            __('Year of passing') => $academic['year_of_passing'] ?? null,
            __('Entrance exam') => $entrance['exam'] ?? null,
            __('Rank') => $entrance['rank'] ?? null,
            __('Allotment number') => $entrance['allotment_no'] ?? null,
        ]" />
    </x-ui.card>

    <x-ui.card :title="__('Documents')" :padding="false">
        @forelse (collect($application['required_documents'] ?? [])->merge($uploaded->keys())->unique() as $type)
            @php($document = $uploaded->get($type))
            <div wire:key="doc-{{ $type }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-ink">{{ \App\Support\Format::label($type) }}</p>
                    @if ($document)
                        <a href="{{ route('files.download', $document['file_id']) }}" class="text-xs text-primary-ink hover:underline">{{ $document['file_name'] }}</a>
                        @if ($document['remarks'])<p class="text-xs text-danger">{{ $document['remarks'] }}</p>@endif
                    @else
                        <p class="text-xs text-ink-muted">{{ __('Not uploaded') }}</p>
                    @endif
                </div>
                @if ($document)<x-ui.status-badge :status="$document['status']" size="sm" />@endif
                @if ($reviewable && $document && $document['status'] !== 'verified')
                    <x-ui.button size="sm" variant="secondary" icon="check" wire:click="reviewDocument('{{ $document['id'] }}', true)">{{ __('Verify') }}</x-ui.button>
                    <x-ui.button size="sm" variant="ghost" icon="x" wire:click="askRejectDocument('{{ $document['id'] }}')">{{ __('Reject') }}</x-ui.button>
                @endif
                {{ $slot }}
            </div>
        @empty
            <x-ui.empty-state compact icon="file-text" :title="__('No documents required')" />
        @endforelse
    </x-ui.card>

    <x-ui.card :title="__('History')">
        <x-ui.timeline :items="collect($application['history'] ?? [])->reverse()->map(fn ($entry) => [
            'title' => \App\Support\Format::label($entry['to']),
            'description' => trim(($entry['remarks'] ?? '').(isset($entry['by']) ? ' · '.__('by :name', ['name' => $entry['by']]) : ''), ' ·'),
            'time' => $entry['at'],
            'icon' => 'circle-check',
            'tone' => in_array($entry['to'], ['rejected', 'ineligible', 'expired', 'withdrawn'], true) ? 'danger' : 'neutral',
        ])->values()->all()" />
    </x-ui.card>
</div>
