<div>
    <x-ui.page-header :title="__('Complaints')" :description="__('Grievances raised by students and staff. Restricted categories are visible only to the committee.')">
        @canapi('feedback.complaints.restricted')
            <x-slot:actions><x-ui.button variant="secondary" icon="plus" wire:click="openCategory">{{ __('Category') }}</x-ui.button></x-slot:actions>
        @endcanapi
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center gap-5 border-b border-line">
        @foreach (['open' => __('Open'), 'in_progress' => __('In progress'), 'resolved' => __('Resolved'), 'closed' => __('Closed')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('filters.status', '{{ $value }}')" :active="$filters['status'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
        <div class="ml-auto flex gap-4 pb-2">
            <x-ui.checkbox wire:model.live="filters.mine" value="1" :label="__('Assigned to me')" />
            <x-ui.checkbox wire:model.live="filters.overdue" value="1" :label="__('Overdue')" />
        </div>
    </div>

    <x-ui.table loading="filters,gotoPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Complaint') }}</x-ui.th>
            <x-ui.th>{{ __('From') }}</x-ui.th>
            <x-ui.th>{{ __('Due') }}</x-ui.th>
            <x-ui.th>{{ __('With') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $c)
            <x-ui.tr wire:key="cp-{{ $c['id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('complaints.show', $c['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $c['subject'] }}</a>
                    <span class="block text-xs text-ink-muted">{{ $c['category']['name'] }}@if ($c['category']['is_restricted']) · <span class="text-danger">{{ __('restricted') }}</span>@endif</span>
                </x-ui.td>
                <x-ui.td :label="__('From')">{{ $c['is_anonymous'] ? __('Anonymous') : $c['raised_by'] }}<span class="block text-xs text-ink-muted">{{ \App\Support\Format::date($c['raised_at']) }}</span></x-ui.td>
                <x-ui.td :label="__('Due')"><span @class(['text-danger font-medium' => $c['overdue']])>{{ $c['due_at'] ? \App\Support\Format::dateTime($c['due_at']) : '—' }}</span></x-ui.td>
                <x-ui.td :label="__('With')">{{ $c['assigned_to'] ?? '—' }}</x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="inbox" :title="__('Nothing here')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    @canapi('feedback.complaints.restricted')
        <x-ui.card class="mt-6" :title="__('Categories')" :padding="false">
            @foreach ($categories as $cat)
                <button type="button" wire:key="cc-{{ $cat['id'] }}" wire:click="openCategory('{{ $cat['id'] }}')" class="flex w-full items-center gap-3 border-b border-line px-5 py-2 text-left text-sm last:border-0 hover:bg-surface-muted">
                    <span class="flex-1 text-ink">{{ $cat['name'] }}</span>
                    <span class="text-xs text-ink-muted">{{ __('resolve within :h h', ['h' => $cat['sla_hours']]) }}</span>
                    @if ($cat['is_restricted'])<x-ui.badge size="sm" tone="danger">{{ __('Restricted') }}</x-ui.badge>@endif
                </button>
            @endforeach
        </x-ui.card>
    @endcanapi

    <x-ui.modal name="complaint-category" :title="__('Complaint category')" size="sm">
        <form wire:submit="saveCategory" id="complaint-category-form" class="space-y-4">
            <x-ui.input :label="__('Name')" wire:model="category.name" required />
            <x-ui.input type="number" min="1" :label="__('Resolve within (hours)')" wire:model="category.sla_hours" required />
            <x-ui.switch wire:model="category.is_restricted" :label="__('Restricted')" :description="__('Only the committee (e.g. ICC, anti-ragging) sees these.')" />
            <x-ui.select :label="__('Status')" wire:model="category.status" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'complaint-category')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="complaint-category-form" loading="saveCategory">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
