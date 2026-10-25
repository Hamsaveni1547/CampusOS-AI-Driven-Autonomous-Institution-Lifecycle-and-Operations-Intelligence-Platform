{{-- <x-ui.spinner class="size-5" label="Loading" /> --}}
@props(['label' => null])
<svg {{ $attributes->class(['animate-spin', 'size-4' => ! $attributes->has('class')]) }} viewBox="0 0 24 24" fill="none"
    @if ($label) role="status" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.2" stroke-width="3"></circle>
    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
</svg>
