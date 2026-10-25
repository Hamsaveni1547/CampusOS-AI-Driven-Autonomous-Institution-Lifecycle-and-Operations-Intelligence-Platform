{{-- Body cell. label is shown before the value when the table is stacked on phones. --}}
@props(['label' => null, 'align' => 'left', 'primary' => false])
<td @if ($label) data-label="{{ $label }}" @endif
    {{ $attributes->class(['px-4 py-3 align-middle', 'text-right' => $align === 'right', 'text-center' => $align === 'center', 'font-medium text-ink' => $primary, 'text-ink-muted' => ! $primary]) }}>
    {{ $slot }}
</td>
