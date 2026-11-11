<div>
    <x-ui.page-header :title="__('Maintenance')" :description="__('Report a fault in a room or a piece of equipment.')">
        <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Report a fault') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['open' => __('Open'), 'in_progress' => __('In progress'), 'resolved' => __('Resolved'), 'closed' => __('Closed')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('filters.status', '{{ $value }}')" :active="$filters['status'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>

    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Fault') }}</x-ui.th>
            <x-ui.th>{{ __('Where') }}</x-ui.th>
            <x-ui.th>{{ __('Raised') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $r)
            <x-ui.tr wire:key="mr-{{ $r['id'] }}">
                <x-ui.td primary>
                    <span class="font-medium text-ink">{{ $r['title'] }}</span> <x-ui.badge size="sm" :tone="['high' => 'danger', 'medium' => 'warning', 'low' => 'neutral'][$r['priority']]">{{ $priorities[$r['priority']] }}</x-ui.badge>
                    @if ($r['description'])<span class="block text-xs text-ink-muted">{{ $r['description'] }}</span>@endif
                    @if ($r['assigned_to'])<span class="block text-xs text-ink-subtle">{{ __('With :n', ['n' => $r['assigned_to']]) }}</span>@endif
                    @if ($r['resolution'])<span class="block text-xs text-success">{{ $r['resolution'] }}</span>@endif
                </x-ui.td>
                <x-ui.td :label="__('Where')">{{ $r['asset'] ? $r['asset']['tag'].' · '.$r['asset']['name'] : '' }}<span class="block text-xs text-ink-muted">{{ $r['room']['code'] ?? '' }}</span></x-ui.td>
                <x-ui.td :label="__('Raised')">{{ \App\Support\Format::date($r['raised_at']) }}<span class="block text-xs text-ink-muted">{{ $r['raised_by'] }}</span></x-ui.td>
                <x-ui.td class="text-right">
                    @canapi('assets.maintenance.manage')
                        @if ($r['status'] === 'open')
                            <x-ui.button size="sm" wire:click="openUpdate('{{ $r['id'] }}', 'in_progress', {{ \Illuminate\Support\Js::from($r) }})">{{ __('Assign') }}</x-ui.button>
                        @elseif ($r['status'] === 'in_progress')
                            <x-ui.button size="sm" icon="check" wire:click="openUpdate('{{ $r['id'] }}', 'resolved', {{ \Illuminate\Support\Js::from($r) }})">{{ __('Resolve') }}</x-ui.button>
                        @elseif ($r['status'] === 'resolved')
                            <x-ui.button size="sm" variant="ghost" wire:click="openUpdate('{{ $r['id'] }}', 'closed', {{ \Illuminate\Support\Js::from($r) }})">{{ __('Close') }}</x-ui.button>
                        @endif
                    @endcanapi
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="wrench" :title="__('Nothing here')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="raise-request" :title="__('Report a fault')">
        <form wire:submit="raise" id="raise-request-form" class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('What is wrong?')" wire:model="form.title" required class="sm:col-span-2" />
            <x-ui.textarea :label="__('Details')" wire:model="form.description" rows="2" optional class="sm:col-span-2" />
            <x-ui.input :label="__('Asset tag')" wire:model="form.asset" optional :hint="__('Printed on the equipment')" />
            <x-ui.select :label="__('Room')" wire:model="form.room_id" :options="$rooms" :placeholder="__('Choose…')" optional />
            <x-ui.select :label="__('Priority')" wire:model="form.priority" :options="$priorities" required />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'raise-request')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="raise-request-form" loading="raise">{{ __('Send') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="update-request" :title="$update['title']" size="sm">
        <form wire:submit="saveUpdate" id="update-request-form" class="space-y-4">
            @if ($update['status'] === 'in_progress')
                <x-ui.input :label="__('Assigned to')" wire:model="update.assigned_to" required :hint="__('Technician or vendor')" />
            @elseif ($update['status'] === 'resolved')
                <x-ui.textarea :label="__('What was done')" wire:model="update.resolution" rows="2" required />
                <x-ui.input type="number" step="0.01" min="0" prefix="₹" :label="__('Cost')" wire:model="update.cost" optional />
            @else
                <p class="text-sm text-ink-muted">{{ __('Close this request?') }}</p>
            @endif
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'update-request')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="update-request-form" loading="saveUpdate">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
