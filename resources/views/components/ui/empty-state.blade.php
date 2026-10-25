{{--
    <x-ui.empty-state icon="building-2" title="No departments yet" description="Add the departments of your institution to get started.">
        <x-ui.button icon="plus">Add department</x-ui.button>
    </x-ui.empty-state>
--}}
@props(['icon' => 'inbox', 'title', 'description' => null, 'compact' => false])
<div {{ $attributes->class(['flex flex-col items-center text-center', $compact ? 'px-4 py-8' : 'px-6 py-14']) }}>
    <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-primary-soft text-primary-ink">
        <x-ui.icon :name="$icon" class="size-6" />
    </div>
    <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $description }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
