<div>
    <x-ui.page-header :title="__('Periods')" :description="__('The bell schedule: periods, breaks and working days used by timetables.')">
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="plus" wire:click="edit">{{ __('New template') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($templates as $template)
            <x-ui.card wire:key="st-{{ $template['id'] }}" :title="$template['name']" :description="collect($template['working_days'])->map(fn ($d) => $days[(string) $d])->implode(', ')" :padding="false">
                <x-slot:actions>
                    @if ($template['is_default'])<x-ui.badge size="sm" tone="primary">{{ __('Default') }}</x-ui.badge>@endif
                    @if ($canManage)
                        <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="edit('{{ $template['id'] }}')" :aria-label="__('Edit')" />
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="delete('{{ $template['id'] }}')" wire:confirm="{{ __('Delete this template?') }}" :aria-label="__('Delete')" />
                    @endif
                </x-slot:actions>
                @foreach ($template['slots'] as $slot)
                    <div wire:key="sl-{{ $slot['id'] }}" @class(['flex justify-between border-b border-line px-5 py-2 text-sm last:border-0', 'bg-surface-muted/50 text-ink-muted' => $slot['is_break']])>
                        <span>{{ $slot['label'] }}</span>
                        <span class="font-mono text-xs">{{ $slot['starts_at'] }}–{{ $slot['ends_at'] }}</span>
                    </div>
                @endforeach
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="clock" :title="__('No period templates yet')" :description="$canManage ? __('Create the bell schedule before building timetables.') : null" />
        @endforelse
    </div>

    @if ($canManage)
        <x-ui.drawer name="template-form" :title="$editingId ? __('Edit periods') : __('New period template')" :description="__('Periods can only be changed while no timetable uses the template.')">
            @if ($form !== [])
                <form wire:submit="save" id="template-form" class="space-y-5">
                    <x-ui.input :label="__('Name')" wire:model="form.name" required :hint="__('e.g. Regular day')" />
                    <div>
                        <p class="mb-2 text-sm font-medium text-ink">{{ __('Working days') }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($days as $value => $label)
                                <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" value="{{ $value }}" wire:model="form.working_days" class="size-4 rounded border-line-strong text-primary focus:ring-primary">{{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="space-y-2">
                        @foreach ($form['slots'] as $index => $slot)
                            <div wire:key="se-{{ $index }}" class="flex items-end gap-2">
                                <x-ui.input :label="$index === 0 ? __('Label') : null" wire:model="form.slots.{{ $index }}.label" class="w-24" :aria-label="__('Label')" />
                                <x-ui.input :label="$index === 0 ? __('Starts') : null" type="time" wire:model="form.slots.{{ $index }}.starts_at" class="w-28" :aria-label="__('Starts')" />
                                <x-ui.input :label="$index === 0 ? __('Ends') : null" type="time" wire:model="form.slots.{{ $index }}.ends_at" class="w-28" :aria-label="__('Ends')" />
                                <x-ui.checkbox wire:model="form.slots.{{ $index }}.is_break" :label="__('Break')" class="mb-2" />
                                <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeSlot({{ $index }})" :aria-label="__('Remove')" />
                            </div>
                        @endforeach
                        <div class="flex gap-2">
                            <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="addSlot(false)">{{ __('Add period') }}</x-ui.button>
                            <x-ui.button size="sm" variant="ghost" icon="plus" wire:click="addSlot(true)">{{ __('Add break') }}</x-ui.button>
                        </div>
                    </div>
                    <x-ui.switch wire:model="form.is_default" :label="__('Default for new timetables')" />
                    @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
                </form>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'template-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="template-form" loading="save">{{ __('Save') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.drawer>
    @endif
</div>
