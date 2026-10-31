<div>
    <x-ui.page-header :title="__('Curricula')" :description="__('Which courses each programme teaches in each term. Changes go through a new version and approval.')">
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="plus" wire:click="openCreate">{{ __('New curriculum') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters :active="$chips">
        <x-ui.select wire:model.live="filters.programme" :options="$programmes" :placeholder="__('All programmes')" class="sm:w-56" :aria-label="__('Programme')" />
        <x-ui.select wire:model.live="filters.status" :options="$statuses" :placeholder="__('Any status')" class="sm:w-40" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="filters" :empty="$curricula === []">
        <x-slot:head>
            <x-ui.th>{{ __('Programme') }}</x-ui.th>
            <x-ui.th>{{ __('Regulation') }}</x-ui.th>
            <x-ui.th>{{ __('Version') }}</x-ui.th>
            <x-ui.th>{{ __('Courses') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($curricula as $curriculum)
            <x-ui.tr wire:key="cu-{{ $curriculum['id'] }}" :href="route('curricula.show', $curriculum['id'])">
                <x-ui.td primary><a href="{{ route('curricula.show', $curriculum['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $curriculum['programme']['name'] ?? '' }}</a></x-ui.td>
                <x-ui.td :label="__('Regulation')">{{ $curriculum['regulation'] }}</x-ui.td>
                <x-ui.td :label="__('Version')">v{{ $curriculum['version'] }}</x-ui.td>
                <x-ui.td :label="__('Courses')">{{ $curriculum['courses_count'] ?? 0 }}</x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$curriculum['status']" :label="$statuses[$curriculum['status']] ?? null" /></x-ui.td>
                <x-ui.td class="text-right">
                    @if ($canManage)
                        <x-ui.button size="sm" variant="ghost" icon="copy" wire:click="openCreate('{{ $curriculum['id'] }}')">{{ __('New version') }}</x-ui.button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="layers" :title="__('No curricula yet')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.modal name="curriculum-form" :title="$form['copy_from'] ? __('New version') : __('New curriculum')" :description="$form['copy_from'] ? __('Starts as a copy of the chosen version.') : null">
        <form wire:submit="create" id="curriculum-form" class="space-y-4">
            <x-ui.select :label="__('Programme')" wire:model="form.programme_id" :options="$programmes" :placeholder="__('Choose')" required :disabled="$form['copy_from'] !== ''" />
            <x-ui.input :label="__('Regulation / scheme')" wire:model="form.regulation" required :hint="__('e.g. 2021')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'curriculum-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="curriculum-form" loading="create">{{ __('Create draft') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
