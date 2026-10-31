@php($tones = ['present' => 'peer-checked:bg-success peer-checked:text-white', 'absent' => 'peer-checked:bg-danger peer-checked:text-white', 'late' => 'peer-checked:bg-warning peer-checked:text-white', 'on_duty' => 'peer-checked:bg-info peer-checked:text-white', 'medical' => 'peer-checked:bg-primary peer-checked:text-white'])
<div>
    @if ($sheet === null)
        <x-ui.page-header :title="__('Take attendance')" :description="__('Classes from your timetable in the last 7 days that still need attendance.')" />

        <x-ui.card :padding="false">
            @forelse ($pending as $period)
                <div wire:key="pd-{{ $period['date'] }}-{{ $period['class_id'] }}-{{ $period['slot_id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                    <div class="w-36 shrink-0 text-sm">
                        <p class="font-medium text-ink">{{ \App\Support\Format::date($period['date'], 'D, d M') }}</p>
                        <p class="font-mono text-xs text-ink-muted">{{ $period['starts_at'] }}–{{ $period['ends_at'] }}</p>
                    </div>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-medium text-ink">{{ $period['course']['code'] }} · {{ $period['course']['title'] }}</p>
                        <p class="text-xs text-ink-muted">{{ $period['section'] }}@if ($period['status'] !== 'scheduled') · {{ \App\Support\Format::label($period['status']) }}@endif</p>
                    </div>
                    <x-ui.button size="sm" icon="clipboard-check" wire:click="open('{{ $period['class_id'] }}', '{{ $period['date'] }}', '{{ $period['slot_id'] }}')">{{ __('Mark') }}</x-ui.button>
                </div>
            @empty
                <x-ui.empty-state compact icon="circle-check" :title="__('All caught up')" :description="__('Every class in the last 7 days has attendance.')" />
            @endforelse
        </x-ui.card>
    @else
        <x-ui.page-header :title="$sheet['class']['course']['code'].' · '.$sheet['class']['course']['title']" :description="$sheet['class']['section'].' · '.\App\Support\Format::date($sheet['date'], 'l, d M Y').($sheet['slot'] ? ' · '.$sheet['slot']['label'].' '.$sheet['slot']['starts_at'] : '')">
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="arrow-left" wire:click="close">{{ __('Back') }}</x-ui.button>
                <x-ui.button variant="secondary" icon="chart-column" :href="route('attendance.class', $sheet['class']['id'])">{{ __('Class attendance') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($sheet['session'])
            <x-ui.alert class="mb-4" :tone="$sheet['can_mark'] ? 'info' : 'warning'">
                {{ __('Marked by :name, :time.', ['name' => $sheet['session']['marked_by'], 'time' => \App\Support\Format::dateTime($sheet['session']['marked_at'])]) }}
                @unless ($sheet['can_mark']) {{ __('The time to change it has passed; students can ask for a correction.') }}@endunless
            </x-ui.alert>
        @endif

        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
            @foreach ($options as $value => $label)
                <x-ui.badge wire:key="cnt-{{ $value }}" size="sm">{{ $label }}: {{ $counts[$value] ?? 0 }}</x-ui.badge>
            @endforeach
            @if ($sheet['can_mark'])
                <span class="ml-auto flex gap-2">
                    <x-ui.button size="sm" variant="ghost" wire:click="setAll('present')">{{ __('All present') }}</x-ui.button>
                    <x-ui.button size="sm" variant="ghost" wire:click="setAll('absent')">{{ __('All absent') }}</x-ui.button>
                </span>
            @endif
        </div>

        <x-ui.card :padding="false">
            @forelse ($sheet['students'] as $student)
                <div wire:key="as-{{ $student['student_id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-2.5 last:border-0">
                    <span class="w-12 font-mono text-xs text-ink-muted">{{ $student['roll_no'] ?? '—' }}</span>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="truncate font-medium text-ink">{{ $student['name'] }}</p>
                        <p class="font-mono text-xs text-ink-subtle">{{ $student['register_no'] }}</p>
                    </div>
                    <div class="flex flex-wrap gap-1" role="radiogroup" aria-label="{{ $student['name'] }}">
                        @foreach ($options as $value => $label)
                            <label wire:key="opt-{{ $student['student_id'] }}-{{ $value }}">
                                <input type="radio" class="peer sr-only" value="{{ $value }}" wire:model.live="statuses.{{ $student['student_id'] }}" @disabled(! $sheet['can_mark'])>
                                <span @class(['inline-block cursor-pointer rounded-md border border-line px-2.5 py-1 text-xs font-medium text-ink-muted transition hover:border-line-strong peer-focus-visible:ring-2 peer-focus-visible:ring-primary', $tones[$value] ?? ''])>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-ui.empty-state compact icon="users" :title="__('No students registered for this class')" />
            @endforelse
        </x-ui.card>

        @if ($sheet['can_mark'] && $sheet['students'] !== [])
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <x-ui.input :label="__('Topic / notes')" wire:model="notes" optional class="min-w-64 flex-1" />
                <x-ui.button icon="check" wire:click="save" loading="save">{{ __('Save attendance') }}</x-ui.button>
            </div>
            @if ($formError)<x-ui.alert class="mt-4" tone="danger">{{ $formError }}</x-ui.alert>@endif
        @endif
    @endif
</div>
