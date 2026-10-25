{{--
    Progress through a multi-step form.
    <x-ui.stepper :steps="['Account', 'Employment', 'Review']" :current="2" />   (current is 1-based)
--}}
@props(['steps' => [], 'current' => 1])
<nav aria-label="{{ __('Progress') }}" {{ $attributes }}>
    <ol class="flex items-center gap-2 sm:gap-4">
        @foreach ($steps as $index => $label)
            @php($number = $index + 1)
            @php($state = $number < $current ? 'done' : ($number === $current ? 'current' : 'upcoming'))
            <li class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3" @if ($state === 'current') aria-current="step" @endif>
                <span @class([
                    'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                    'bg-primary text-on-primary' => $state !== 'upcoming',
                    'border border-line-strong text-ink-muted' => $state === 'upcoming',
                ])>
                    @if ($state === 'done')<x-ui.icon name="check" class="size-4" />@else{{ $number }}@endif
                </span>
                <span @class(['hidden truncate text-sm sm:block', 'font-medium text-ink' => $state === 'current', 'text-ink-muted' => $state !== 'current'])>{{ $label }}</span>
                @unless ($loop->last)
                    <span @class(['h-px flex-1', 'bg-primary' => $state === 'done', 'bg-line' => $state !== 'done'])></span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
