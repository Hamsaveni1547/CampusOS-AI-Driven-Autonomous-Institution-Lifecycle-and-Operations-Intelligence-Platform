{{--
    Data table. Use with <x-ui.th>, <x-ui.tr>, <x-ui.td>.
    <x-ui.table>
        <x-slot:head>
            <x-ui.th sortable="name" :sort="$sort">Name</x-ui.th>
            <x-ui.th>Status</x-ui.th>
            <x-ui.th align="right"><span class="sr-only">Actions</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows as $row) <x-ui.tr>…</x-ui.tr> @endforeach
        <x-slot:emptyState><x-ui.empty-state … /></x-slot:emptyState>   (shown instead of rows when :empty="true")
    </x-ui.table>
    loading: Livewire target(s) — rows fade while loading.   stacked: on phones each row becomes a card (td need :label).
--}}
@props(['loading' => null, 'empty' => false, 'stacked' => true, 'sticky' => false])
<div {{ $attributes->class(['relative overflow-hidden rounded-lg border border-line bg-surface shadow-sm dark:shadow-none']) }}>
    <div class="overflow-x-auto">
        <table @class(['w-full text-left text-sm', 'cos-stacked' => $stacked])>
            @isset($head)
                <thead @class(['border-b border-line bg-surface-muted/60 text-xs font-medium text-ink-muted', 'sticky top-0 z-10' => $sticky])>
                    <tr>{{ $head }}</tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-line"
                @if ($loading) wire:loading.class="opacity-60 transition-opacity" wire:target="{{ $loading }}" @endif>
                @if ($empty)
                    <tr class="cos-empty-row">
                        <td colspan="100">{{ $emptyState ?? '' }}</td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
    @isset($footer)
        <div class="border-t border-line px-4 py-3">{{ $footer }}</div>
    @endisset
</div>
