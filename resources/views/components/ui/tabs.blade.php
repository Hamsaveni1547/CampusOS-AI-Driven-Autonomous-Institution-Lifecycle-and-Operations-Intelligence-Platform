{{--
    Client-side tabs (all panels rendered). For server-side tabs use links with <x-ui.tab-link>.
    <x-ui.tabs :tabs="['overview' => 'Overview', 'qualifications' => 'Qualifications']" active="overview" param="tab">
        <x-ui.tab-panel name="overview">…</x-ui.tab-panel>
        <x-ui.tab-panel name="qualifications">…</x-ui.tab-panel>
    </x-ui.tabs>
    tabs values may be ['label' => .., 'count' => ..] to show a counter.
--}}
@props(['tabs' => [], 'active' => null, 'param' => null])
<div x-data="tabs({ active: @js($active ?? array_key_first($tabs)), param: @js($param) })" {{ $attributes }}>
    <div class="border-b border-line">
        <div role="tablist" class="-mb-px flex gap-5 overflow-x-auto" x-on:keydown="keydown($event)">
            @foreach ($tabs as $name => $tab)
                @php($label = is_array($tab) ? $tab['label'] : $tab)
                @php($count = is_array($tab) ? ($tab['count'] ?? null) : null)
                <button type="button" role="tab" id="tab-{{ $name }}" aria-controls="panel-{{ $name }}"
                    :aria-selected="active === @js($name)" :tabindex="active === @js($name) ? 0 : -1"
                    x-on:click="select(@js($name))"
                    class="flex shrink-0 items-center gap-2 border-b-2 px-0.5 pb-3 pt-1 text-sm font-medium transition"
                    :class="active === @js($name) ? 'border-primary text-primary-ink' : 'border-transparent text-ink-muted hover:border-line-strong hover:text-ink'">
                    {{ $label }}
                    @if ($count !== null)
                        <span class="tabular rounded-full bg-surface-muted px-1.5 text-xs text-ink-muted">{{ $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
    <div class="pt-6">{{ $slot }}</div>
</div>
