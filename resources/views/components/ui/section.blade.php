{{--
    Settings-style block: title + description on the left, content on the right (stacked on mobile).
    <x-ui.section title="Password rules" description="Applies to every account.">...</x-ui.section>
--}}
@props(['title', 'description' => null])
<div {{ $attributes->class(['grid gap-4 py-6 first:pt-0 last:pb-0 md:grid-cols-3 md:gap-8']) }}>
    <div>
        <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 text-sm text-ink-muted">{{ $description }}</p>
        @endif
    </div>
    <div class="space-y-4 md:col-span-2">{{ $slot }}</div>
</div>
