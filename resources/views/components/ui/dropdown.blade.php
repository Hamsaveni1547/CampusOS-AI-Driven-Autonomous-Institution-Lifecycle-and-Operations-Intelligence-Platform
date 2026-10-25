{{--
    <x-ui.dropdown align="right">
        <x-slot:trigger><x-ui.button variant="secondary" icon-right="chevron-down">Actions</x-ui.button></x-slot:trigger>
        <x-ui.dropdown-item icon="pencil" wire:click="edit">Edit</x-ui.dropdown-item>
        <x-ui.dropdown-item icon="trash-2" tone="danger">Delete</x-ui.dropdown-item>
    </x-ui.dropdown>
    The trigger's first element becomes the menu button.
--}}
@props(['align' => 'right', 'width' => 'w-56'])
<div x-data="dropdown" x-on:keydown.escape.prevent.stop="close()" {{ $attributes->class(['relative inline-block text-left']) }}>
    <div x-ref="button" x-on:click="toggle()" x-on:keydown.arrow-down.prevent="show()" class="contents" aria-haspopup="menu" :aria-expanded="open">
        {{ $trigger }}
    </div>
    <div
        x-ref="panel"
        x-show="open"
        x-cloak
        x-on:click.outside="close(false)"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.tab="close(false)"
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-end="scale-95 opacity-0"
        role="menu"
        @class([
            'absolute z-30 mt-2 rounded-lg border border-line bg-surface-raised p-1 shadow-lg focus:outline-none',
            $width,
            'right-0 origin-top-right' => $align === 'right',
            'left-0 origin-top-left' => $align === 'left',
        ])
    >
        {{ $slot }}
    </div>
</div>
