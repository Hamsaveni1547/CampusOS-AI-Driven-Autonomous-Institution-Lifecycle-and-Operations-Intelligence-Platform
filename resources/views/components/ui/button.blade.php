{{--
    <x-ui.button>Save</x-ui.button>
    <x-ui.button variant="secondary" icon="plus" href="{{ route('x') }}">Add</x-ui.button>
    <x-ui.button variant="danger" wire:click="delete" loading="delete">Delete</x-ui.button>
    <x-ui.button variant="ghost" icon="x" square label="Close" />

    variant: primary | secondary | soft | ghost | danger | link      size: sm | md | lg
    loading: Livewire action/property name; shows a spinner and disables while it runs.
             Use loading="1" on submit buttons to react to any request of the component.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'type' => 'button',
    'loading' => null,
    'square' => false,
    'label' => null,
    'disabled' => false,
    'block' => false,
])
@php
    $variants = [
        'primary' => 'bg-primary text-on-primary shadow-xs hover:bg-primary-hover',
        'secondary' => 'border border-line-strong bg-surface text-ink shadow-xs hover:bg-surface-muted',
        'soft' => 'bg-primary-soft text-primary-ink hover:bg-primary-soft/70',
        'ghost' => 'text-ink-muted hover:bg-surface-muted hover:text-ink',
        'danger' => 'bg-danger text-white shadow-xs hover:bg-danger-hover',
        'link' => 'text-primary underline-offset-4 hover:underline px-0! h-auto!',
    ];
    $sizes = $square
        ? ['sm' => 'size-8', 'md' => 'size-9', 'lg' => 'size-10']
        : ['sm' => 'h-8 gap-1.5 px-2.5 text-xs', 'md' => 'h-9 gap-2 px-3.5 text-sm', 'lg' => 'h-10 gap-2 px-4 text-sm'];
    $iconSize = $size === 'sm' ? 'size-3.5' : 'size-4';

    $classes = \Illuminate\Support\Arr::toCssClasses([
        'relative inline-flex select-none items-center justify-center rounded-md font-medium whitespace-nowrap transition duration-150',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
        'disabled:pointer-events-none disabled:opacity-55 aria-disabled:pointer-events-none aria-disabled:opacity-55',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        'w-full' => $block,
    ]);

    $wireTarget = $loading !== null && $loading !== '1' && $loading !== true ? $loading : null;
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }}
    @if ($href) href="{{ $href }}" @if ($disabled) aria-disabled="true" tabindex="-1" @endif
    @else type="{{ $type }}" @disabled($disabled) @endif
    @if ($square && $label) aria-label="{{ $label }}" title="{{ $label }}" @endif
    @if ($loading) wire:loading.attr="disabled" @if ($wireTarget) wire:target="{{ $wireTarget }}" @endif @endif
    {{ $attributes->class($classes) }}
>
    @if ($loading)
        <span wire:loading.flex @if ($wireTarget) wire:target="{{ $wireTarget }}" @endif class="absolute inset-0 hidden items-center justify-center">
            <x-ui.spinner :class="$iconSize" />
        </span>
        <span wire:loading.class="invisible" @if ($wireTarget) wire:target="{{ $wireTarget }}" @endif class="inline-flex items-center gap-[inherit]">
    @endif

    @if ($icon)
        <x-ui.icon :name="$icon" :class="$iconSize" />
    @endif

    @if (! $square)
        {{ $slot }}
    @endif

    @if ($iconRight)
        <x-ui.icon :name="$iconRight" :class="$iconSize" />
    @endif

    @if ($loading)
        </span>
    @endif
</{{ $tag }}>
