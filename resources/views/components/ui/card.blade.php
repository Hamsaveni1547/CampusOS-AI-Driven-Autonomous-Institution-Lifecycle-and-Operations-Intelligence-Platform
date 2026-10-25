{{--
    <x-ui.card title="Profile" description="Your personal details">
        <x-slot:actions><x-ui.button size="sm">Edit</x-ui.button></x-slot:actions>
        ...body...
        <x-slot:footer>...</x-slot:footer>
    </x-ui.card>
    padding: false removes body padding (for tables/lists).
--}}
@props(['title' => null, 'description' => null, 'padding' => true])
<section {{ $attributes->class(['overflow-hidden rounded-lg border border-line bg-surface shadow-sm dark:shadow-none']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-sm font-semibold text-ink">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5' => $padding])>{{ $slot }}</div>

    @isset($footer)
        <footer {{ $footer->attributes->class(['flex items-center justify-end gap-2 border-t border-line bg-surface-muted/40 px-5 py-3']) }}>
            {{ $footer }}
        </footer>
    @endisset
</section>
