<div>
    <x-ui.page-header :title="__('My attendance')" :description="($summary['term']['name'] ?? __('No current term')).' · '.__('minimum :n% per course', ['n' => $threshold])" />

    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($summary['courses'] as $course)
            <button type="button" wire:key="mc-{{ $course['class_id'] }}" wire:click="$set('class', '{{ $course['class_id'] }}')"
                @class(['rounded-lg border bg-surface p-5 text-left shadow-sm transition hover:border-primary/40 dark:shadow-none', 'border-primary' => $class === $course['class_id'], 'border-line' => $class !== $course['class_id']])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">{{ $course['course']['title'] }}</p>
                        <p class="font-mono text-xs text-ink-muted">{{ $course['course']['code'] }}</p>
                    </div>
                    <x-attendance.standing :standing="$course['standing']" />
                </div>
                <div class="mt-3"><x-ui.progress :value="(int) round($course['percentage'] ?? 0)" show-value :tone="($course['percentage'] ?? 100) < $threshold ? 'danger' : 'success'" /></div>
                <p class="mt-1 text-xs text-ink-subtle">{{ __(':a of :h classes', ['a' => $course['attended'], 'h' => $course['held']]) }}</p>
            </button>
        @empty
            <x-ui.empty-state icon="clipboard-check" :title="__('No courses yet')" :description="__('Your courses appear after term registration.')" />
        @endforelse
    </div>

    @if ($detail)
        <x-ui.card :title="$detail['class']['course']['code'].' · '.$detail['class']['course']['title']" :padding="false">
            @forelse ($detail['records'] as $record)
                <div wire:key="mr-{{ $record['session_id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-2.5 text-sm last:border-0">
                    <span class="w-32 shrink-0 text-ink">{{ \App\Support\Format::date($record['date'], 'D, d M') }}@if ($record['slot']) <span class="text-xs text-ink-muted">{{ $record['slot'] }}</span>@endif</span>
                    <x-ui.status-badge :status="$record['status']" :label="$labels[$record['status']] ?? null" size="sm" />
                    <span class="min-w-0 flex-1 text-xs text-ink-muted">
                        @if ($record['correction'])
                            {{ __('Correction to :s: :state', ['s' => $labels[$record['correction']['requested_status']] ?? '', 'state' => \App\Support\Format::label($record['correction']['status'])]) }}@if ($record['correction']['remarks']) · {{ $record['correction']['remarks'] }}@endif
                        @endif
                    </span>
                    @if ($record['status'] !== 'present' && ($record['correction']['status'] ?? null) !== 'pending')
                        <x-ui.button size="sm" variant="ghost" wire:click="openCorrection('{{ $record['session_id'] }}')">{{ __('Ask for correction') }}</x-ui.button>
                    @endif
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-ink-muted">{{ __('No classes marked yet.') }}</p>
            @endforelse
        </x-ui.card>
    @endif

    <x-ui.modal name="attendance-correction" :title="__('Ask for a correction')" size="sm">
        <form wire:submit="requestCorrection" id="attendance-correction-form" class="space-y-4">
            <x-ui.select :label="__('Should be')" wire:model="correction.requested_status" :options="$labels" required />
            <x-ui.textarea :label="__('Reason')" wire:model="correction.reason" rows="2" required :hint="__('e.g. I was representing the college at an event.')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'attendance-correction')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="attendance-correction-form" loading="requestCorrection">{{ __('Send') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
