{{-- <x-ui.breadcrumbs :items="['People' => null, 'Staff' => route('staff.index'), 'Anita Rao' => null]" /> --}}
@props(['items' => []])
@if ($items !== [])
    <nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes->class(['min-w-0']) }}>
        <ol class="flex min-w-0 items-center gap-1.5 text-sm text-ink-muted">
            @foreach ($items as $label => $url)
                <li class="flex min-w-0 items-center gap-1.5">
                    @unless ($loop->first)
                        <x-ui.icon name="chevron-right" class="size-3.5 text-ink-subtle" />
                    @endunless
                    @if ($url && ! $loop->last)
                        <a href="{{ $url }}" class="truncate hover:text-ink">{{ $label }}</a>
                    @else
                        <span @class(['truncate', 'font-medium text-ink' => $loop->last]) @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
