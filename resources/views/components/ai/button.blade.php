{{--
    <x-ai.button wire:click="suggest" loading="suggest">Suggest</x-ai.button> — an AI action, visually marked with a sparkle.
--}}
@props(['loading' => null, 'size' => 'sm'])
<button type="button" {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-md border border-violet-300 bg-violet-50 font-medium text-violet-700 transition hover:bg-violet-100 disabled:opacity-60 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300',
    'px-2.5 py-1 text-xs' => $size === 'sm',
    'px-3 py-1.5 text-sm' => $size !== 'sm',
]) }} @if ($loading) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif>
    <x-ui.icon name="sparkles" class="size-3.5" @if ($loading) wire:loading.remove wire:target="{{ $loading }}" @endif />
    @if ($loading)<x-ui.spinner class="size-3.5" wire:loading wire:target="{{ $loading }}" />@endif
    {{ $slot }}
</button>
