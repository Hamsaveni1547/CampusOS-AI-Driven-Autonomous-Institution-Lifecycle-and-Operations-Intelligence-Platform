{{-- Table row. href makes the whole row clickable (links inside still work). --}}
@props(['href' => null, 'selected' => false])
<tr @if ($href) x-data x-on:click="if (! $event.target.closest('a, button, input, label, [role=menuitem]')) window.location = @js($href)" @endif
    {{ $attributes->class(['transition-colors', 'cursor-pointer hover:bg-surface-muted/50' => $href, 'hover:bg-surface-muted/30' => ! $href, 'bg-primary-soft/60' => $selected]) }}>
    {{ $slot }}
</tr>
