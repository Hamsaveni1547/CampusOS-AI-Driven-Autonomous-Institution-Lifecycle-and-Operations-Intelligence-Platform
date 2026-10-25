<div>
    <x-ui.page-header :title="__('Notification templates')" :description="__('Change the wording of emails CampusOS sends. Placeholders in {braces} are filled in automatically.')" />

    @if ($canDeliveries)
        <div class="mb-6 flex gap-5 border-b border-line">
            <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'templates')" :active="$tab === 'templates'">{{ __('Templates') }}</x-ui.tab-link>
            <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'deliveries')" :active="$tab === 'deliveries'">{{ __('Delivery log') }}</x-ui.tab-link>
        </div>
    @endif

    @if ($tab === 'templates')
        <x-ui.table :empty="$templates === []">
            <x-slot:head>
                <x-ui.th>{{ __('Notification') }}</x-ui.th>
                <x-ui.th>{{ __('Category') }}</x-ui.th>
                <x-ui.th>{{ __('Channels') }}</x-ui.th>
            </x-slot:head>
            @foreach ($templates as $template)
                <x-ui.tr wire:key="t-{{ $template['key'] }}">
                    <x-ui.td primary>
                        <p class="font-medium text-ink">{{ $template['description'] }}</p>
                        <p class="font-mono text-xs text-ink-subtle">{{ $template['key'] }}</p>
                    </x-ui.td>
                    <x-ui.td :label="__('Category')">{{ \App\Support\Format::label($template['category']) }}</x-ui.td>
                    <x-ui.td :label="__('Channels')">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($template['channels'] as $channel)
                                <x-ui.button size="sm" variant="secondary" wire:click="edit('{{ $template['key'] }}', '{{ $channel['channel'] }}')">
                                    {{ \App\Support\Format::label($channel['channel']) }}
                                    @if ($channel['custom'])<x-ui.badge size="sm" tone="primary">{{ __('Custom') }}</x-ui.badge>@endif
                                </x-ui.button>
                            @endforeach
                        </div>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="mail" :title="__('No templates')" /></x-slot:emptyState>
        </x-ui.table>
    @elseif ($deliveries)
        <div class="mb-4 flex flex-wrap gap-3">
            <x-ui.select wire:model.live="deliveryFilters.status" :options="['sent' => __('Sent'), 'failed' => __('Failed')]" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
            <x-ui.select wire:model.live="deliveryFilters.channel" :options="['mail' => __('Email')]" :placeholder="__('Any channel')" class="sm:w-40" :aria-label="__('Channel')" />
        </div>
        <x-ui.table :empty="$deliveries->isEmpty()" loading="deliveryFilters,nextPage,previousPage">
            <x-slot:head>
                <x-ui.th>{{ __('Notification') }}</x-ui.th>
                <x-ui.th>{{ __('Channel') }}</x-ui.th>
                <x-ui.th>{{ __('Status') }}</x-ui.th>
                <x-ui.th>{{ __('When') }}</x-ui.th>
            </x-slot:head>
            @foreach ($deliveries->items as $delivery)
                <x-ui.tr wire:key="d-{{ $delivery['id'] }}">
                    <x-ui.td primary>
                        {{ \App\Support\Format::label(class_basename(str_replace('\\', '/', $delivery['notification_type']))) }}
                        @if ($delivery['error'])<p class="text-xs text-danger">{{ $delivery['error'] }}</p>@endif
                    </x-ui.td>
                    <x-ui.td :label="__('Channel')">{{ \App\Support\Format::label($delivery['channel']) }}</x-ui.td>
                    <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$delivery['status']" /></x-ui.td>
                    <x-ui.td :label="__('When')"><x-ui.time :value="$delivery['created_at']" /></x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="mail" :title="__('Nothing sent yet')" /></x-slot:emptyState>
            <x-slot:footer>
                <div class="flex justify-end gap-2 px-4 py-3">
                    <x-ui.button size="sm" variant="secondary" wire:click="previousPage" :disabled="$previousCursors === []">{{ __('Newer') }}</x-ui.button>
                    <x-ui.button size="sm" variant="secondary" wire:click="nextPage('{{ $deliveries->nextCursor }}')" :disabled="$deliveries->nextCursor === null">{{ __('Older') }}</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.table>
    @endif

    <x-ui.drawer name="template-form" size="lg" :title="$editingKey ? \App\Support\Format::label($editingKey) : ''" :description="$isCustom ? __('Customised for this institution.') : __('Using the built-in wording.')">
        <form wire:submit="save" id="template-form" class="space-y-5">
            <x-ui.input :label="__('Subject')" wire:model="form.subject" required />
            <x-ui.textarea :label="__('Body')" wire:model="form.body" rows="12" required class="font-mono" />
            @if ($placeholders !== [])
                <div>
                    <p class="mb-2 text-sm font-medium text-ink">{{ __('Placeholders') }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($placeholders as $placeholder)<x-ui.code :value="'{'.$placeholder.'}'" />@endforeach
                    </div>
                </div>
            @endif
            <x-ui.switch wire:model="form.is_active" :label="__('Use this custom template')" :description="__('Off: the built-in wording is sent instead.')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            @if ($isCustom)
                <x-ui.button variant="ghost" class="mr-auto" wire:click="resetTemplate" loading="resetTemplate">{{ __('Reset to built-in') }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'template-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="template-form" loading="save">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
