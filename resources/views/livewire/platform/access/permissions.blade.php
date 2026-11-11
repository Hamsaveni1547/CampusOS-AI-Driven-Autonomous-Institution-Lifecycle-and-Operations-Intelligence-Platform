<div>
    <x-ui.page-header :title="__('Permissions')" :description="__('Everything a role can be allowed to do, by module. Change which roles have a permission on the Roles page.')" />

    <div class="mb-4 max-w-sm"><x-ui.input type="search" icon="search" wire:model.live.debounce.300ms="search" :placeholder="__('Search permissions…')" /></div>

    <div class="space-y-6">
        @forelse ($groups as $g)
            <x-ui.card wire:key="pg-{{ $g['module'] }}" :title="$g['label']" :padding="false">
                @foreach ($g['permissions'] as $p)
                    <div wire:key="pp-{{ $p['name'] }}" class="flex flex-wrap items-start gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-xs text-ink">{{ $p['name'] }}@if ($p['sensitive'])<x-ui.badge size="sm" tone="warning" class="ml-2">{{ __('Sensitive') }}</x-ui.badge>@endif</p>
                            <p class="text-ink-muted">{{ $p['description'] }}</p>
                        </div>
                        <div class="flex max-w-md flex-wrap gap-1">
                            @forelse ($p['roles'] as $r)
                                <x-ui.badge wire:key="pr-{{ $p['name'] }}-{{ $r }}" size="sm">{{ $roleNames[$r] ?? $r }}</x-ui.badge>
                            @empty
                                <span class="text-xs text-ink-subtle">{{ __('No built-in role') }}</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="key-round" :title="__('No permissions match')" />
        @endforelse
    </div>
</div>
