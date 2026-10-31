<div>
    <x-ui.page-header :title="__('My exams')" :description="__('Your end-term exams, eligibility and hall tickets.')">
        <x-slot:actions><x-ui.button variant="secondary" icon="award" :href="route('my-results.show')">{{ __('Results') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="space-y-6">
        @forelse ($cycles as $cycle)
            <x-ui.card wire:key="mc-{{ $cycle['id'] }}" :title="$cycle['name']" :padding="false">
                <x-slot:actions>
                    @if ($cycle['hall_ticket'])
                        <x-ui.button size="sm" icon="ticket" :href="route('hall-ticket.show', $cycle['id'])">{{ __('Hall ticket') }}</x-ui.button>
                    @endif
                </x-slot:actions>
                @foreach ($cycle['courses'] as $course)
                    <div wire:key="mc-{{ $cycle['id'] }}-{{ $course['code'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                        <span class="min-w-0 flex-1"><span class="font-medium text-ink">{{ $course['code'] }}</span> <span class="text-ink-muted">{{ $course['title'] }}</span>@if ($course['kind'] === 'backlog') <x-ui.badge size="sm" tone="warning">{{ __('Backlog') }}</x-ui.badge>@endif</span>
                        <span class="text-xs text-ink-muted">@if ($course['exam']){{ \App\Support\Format::date($course['exam']['date'], 'D, d M') }} · {{ $course['exam']['starts_at'] }}–{{ $course['exam']['ends_at'] }}@else{{ __('Date not set') }}@endif</span>
                        <span class="w-56 text-right">
                            <x-ui.status-badge :status="$course['eligibility']" size="sm" />
                            @foreach ($course['reasons'] as $reason)<span class="block text-xs text-danger">{{ $reason }}</span>@endforeach
                        </span>
                    </div>
                @endforeach
                @if ($cycle['backlogs_open'] !== [])
                    <div class="border-t border-line p-5">
                        <p class="mb-3 text-sm font-medium text-ink">{{ __('Register for courses you have not cleared') }}@if ($cycle['registration_ends_on']) <span class="font-normal text-ink-muted">· {{ __('by :d', ['d' => \App\Support\Format::date($cycle['registration_ends_on'])]) }}</span>@endif</p>
                        <div class="space-y-2">
                            @foreach ($cycle['backlogs_open'] as $backlog)
                                <x-ui.checkbox wire:key="bl-{{ $cycle['id'] }}-{{ $backlog['course_id'] }}" wire:model="backlogs.{{ $cycle['id'] }}" value="{{ $backlog['course_id'] }}" :label="$backlog['code'].' · '.$backlog['title']" :description="__('Last grade: :g', ['g' => $backlog['grade']])" />
                            @endforeach
                        </div>
                        <x-ui.button class="mt-4" size="sm" wire:click="registerBacklogs('{{ $cycle['id'] }}')" loading="registerBacklogs">{{ __('Register') }}</x-ui.button>
                    </div>
                @endif
                @if ($cycle['courses'] === [] && $cycle['backlogs_open'] === [])
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('You are not registered for this exam.') }}</p>
                @endif
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="file-check" :title="__('No exams right now')" :description="__('Exams appear here when registration opens.')" />
        @endforelse
    </div>
</div>
