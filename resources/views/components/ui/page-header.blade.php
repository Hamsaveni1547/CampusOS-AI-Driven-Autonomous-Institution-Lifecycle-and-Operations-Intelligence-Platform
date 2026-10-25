{{--
    <x-ui.page-header title="Departments" description="Academic and administrative units">
        <x-slot:actions><x-ui.button icon="plus">Add department</x-ui.button></x-slot:actions>
        <x-slot:meta><x-ui.status-badge status="active" /></x-slot:meta>
    </x-ui.page-header>
    Optional "back" URL shows a back link above the title. Slot "tabs" renders under the header.
--}}
@props(['title', 'description' => null, 'back' => null, 'backLabel' => null])
<div {{ $attributes->class(['mb-6']) }}>
    @if ($back)
        <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-xs font-medium text-ink-muted hover:text-ink">
            <x-ui.icon name="arrow-left" class="size-3.5" />
            {{ $backLabel ?? __('Back') }}
        </a>
    @endif
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="truncate text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ $title }}</h1>
                {{ $meta ?? '' }}
            </div>
            @if ($description)
                <p class="mt-1 max-w-3xl text-sm text-ink-muted">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
    @isset($tabs)
        <div class="mt-5">{{ $tabs }}</div>
    @endisset
</div>
