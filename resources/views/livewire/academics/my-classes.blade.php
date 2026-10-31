<div>
    <x-ui.page-header :title="__('My classes')" :description="($teaching['term']['name'] ?? $courses['term']['name'] ?? __('No current term')).(isset($teaching) ? ' · '.__(':n hours a week', ['n' => $teaching['weekly_hours']]) : '')" />

    @if ($teaching !== null)
        <h2 class="mb-3 text-base font-semibold text-ink">{{ __('Teaching') }}</h2>
        <div class="mb-8 grid gap-4 md:grid-cols-2">
            @forelse ($teaching['classes'] as $class)
                <a wire:key="tc-{{ $class['id'] }}" href="{{ route('classes.show', $class['id']) }}" wire:navigate class="rounded-lg border border-line bg-surface p-5 shadow-sm transition hover:border-primary/40 dark:shadow-none">
                    <p class="font-semibold text-ink">{{ $class['course']['title'] }}</p>
                    <p class="text-sm text-ink-muted"><span class="font-mono">{{ $class['course']['code'] }}</span> · {{ $class['section']['batch'] }} {{ $class['section']['name'] }}</p>
                    <p class="mt-2 text-xs text-ink-subtle">{{ collect($class['components'])->map(fn ($c) => ($components[$c['component']] ?? $c['component']).' '.$c['weekly_hours'].'h')->implode(' · ') }}</p>
                    <div class="mt-3"><x-ui.progress :value="$class['coverage_percent']" show-value :label="__('Syllabus covered')" /></div>
                </a>
            @empty
                <x-ui.empty-state compact icon="briefcase" :title="__('No classes allocated to you this term')" />
            @endforelse
        </div>
    @endif

    @if ($courses !== null)
        <h2 class="mb-3 text-base font-semibold text-ink">{{ __('My courses') }}</h2>
        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($courses['courses'] as $class)
                <a wire:key="sc-{{ $class['id'] }}" href="{{ route('classes.show', $class['id']) }}" wire:navigate class="rounded-lg border border-line bg-surface p-5 shadow-sm transition hover:border-primary/40 dark:shadow-none">
                    <p class="font-semibold text-ink">{{ $class['course']['title'] }}</p>
                    <p class="text-sm text-ink-muted"><span class="font-mono">{{ $class['course']['code'] }}</span> · {{ __(':c credits', ['c' => $class['course']['credits']]) }}</p>
                    <p class="mt-2 text-xs text-ink-subtle">{{ collect($class['faculty'])->pluck('name')->unique()->implode(', ') ?: __('Teacher not assigned yet') }}</p>
                    <div class="mt-3"><x-ui.progress :value="$class['coverage_percent']" show-value :label="__('Syllabus covered')" /></div>
                </a>
            @empty
                <x-ui.empty-state compact icon="book-open" :title="__('No courses yet')" :description="__('Your courses appear once your section is set and courses are offered.')" />
            @endforelse
        </div>
    @endif

    @if ($teaching === null && $courses === null)
        <x-ui.empty-state icon="book-open" :title="__('Nothing to show')" :description="__('This page is for teachers and students.')" />
    @endif
</div>
