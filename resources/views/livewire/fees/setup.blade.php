@php($money = fn ($v) => '₹'.number_format((float) $v, 2))
<div>
    <x-ui.page-header :title="__('Fee setup')" :description="__('Fee heads, and fee structures by programme, batch, quota and semester.')">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="plus" wire:click="openHead">{{ __('Fee head') }}</x-ui.button>
            <x-ui.button icon="plus" wire:click="openStructure">{{ __('Fee structure') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card :title="__('Fee heads')" :padding="false">
            @forelse ($heads as $h)
                <button type="button" wire:key="h-{{ $h['id'] }}" wire:click="openHead('{{ $h['id'] }}')" class="flex w-full items-center gap-3 border-b border-line px-5 py-2.5 text-left text-sm last:border-0 hover:bg-surface-muted">
                    <span class="w-14 font-mono text-xs text-ink-muted">{{ $h['code'] }}</span>
                    <span class="flex-1 text-ink">{{ $h['name'] }}</span>
                    @if ($h['status'] !== 'active')<x-ui.status-badge :status="$h['status']" size="sm" />@endif
                </button>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('Add heads such as tuition, examination, library.') }}</p>
            @endforelse
        </x-ui.card>

        <div class="lg:col-span-2">
            <x-ui.table :empty="$structures === []">
                <x-slot:head>
                    <x-ui.th>{{ __('Structure') }}</x-ui.th>
                    <x-ui.th>{{ __('Applies to') }}</x-ui.th>
                    <x-ui.th>{{ __('Total') }}</x-ui.th>
                    <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
                </x-slot:head>
                @foreach ($structures as $s)
                    <x-ui.tr wire:key="s-{{ $s['id'] }}">
                        <x-ui.td primary>
                            <button type="button" wire:click="openStructure('{{ $s['id'] }}')" class="font-medium text-ink hover:underline">{{ $s['name'] }}</button>
                            <span class="block text-xs text-ink-muted">{{ __('Due :d', ['d' => \App\Support\Format::date($s['due_on'])]) }} · {{ __(':n raised', ['n' => $s['demands'] ?? 0]) }}</span>
                        </x-ui.td>
                        <x-ui.td :label="__('Applies to')">{{ $s['programme']['code'] }}@if ($s['admission_year']) · {{ $s['admission_year'] }}@endif @if ($s['term_number']) · {{ __('sem :n', ['n' => $s['term_number']]) }}@endif @if ($s['quota']) · {{ $quotas[$s['quota']] ?? $s['quota'] }}@endif</x-ui.td>
                        <x-ui.td :label="__('Total')"><span class="font-semibold">{{ $money($s['total']) }}</span></x-ui.td>
                        <x-ui.td class="text-right">
                            @canapi('finance.demands.manage')
                                <x-ui.button size="sm" icon="send" wire:click="generate('{{ $s['id'] }}')" wire:confirm="{{ __('Raise this fee for every student it applies to? Students who already have it are skipped.') }}">{{ __('Raise fees') }}</x-ui.button>
                            @endcanapi
                        </x-ui.td>
                    </x-ui.tr>
                @endforeach
                <x-slot:emptyState><x-ui.empty-state compact icon="wallet" :title="__('No fee structures yet')" /></x-slot:emptyState>
            </x-ui.table>
        </div>
    </div>

    <x-ui.modal name="head-form" :title="$head['id'] ? __('Edit fee head') : __('New fee head')" size="sm">
        <form wire:submit="saveHead" id="head-form-el" class="space-y-4">
            <x-ui.input :label="__('Code')" wire:model="head.code" required placeholder="TUI" />
            <x-ui.input :label="__('Name')" wire:model="head.name" required :hint="__('e.g. Tuition fee')" />
            <x-ui.input :label="__('Description')" wire:model="head.description" optional />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'head-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="head-form-el" loading="saveHead">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="structure-form" :title="($structure['id'] ?? '') ? __('Edit fee structure') : __('New fee structure')" size="lg">
        <form wire:submit="saveStructure" id="structure-form-el" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('Name')" wire:model="structure.name" required :hint="__('e.g. MCA 2026 batch, semester 1')" class="sm:col-span-2" />
                <x-ui.select :label="__('Programme')" wire:model="structure.programme_id" :options="$programmes" :placeholder="__('Choose…')" required />
                <x-ui.input type="date" :label="__('Due on')" wire:model="structure.due_on" required />
                <x-ui.input type="number" :label="__('Admission year')" wire:model="structure.admission_year" optional :hint="__('Empty: every batch')" />
                <x-ui.input type="number" min="1" :label="__('Semester')" wire:model="structure.term_number" optional :hint="__('Empty: any semester')" />
                <x-ui.select :label="__('Quota')" wire:model="structure.quota" :options="$quotas" :placeholder="__('Every quota')" optional />
            </div>
            <div class="space-y-2">
                <p class="text-sm font-medium text-ink">{{ __('Amounts') }}</p>
                @foreach ($structure['items'] ?? [] as $i => $item)
                    <div wire:key="it-{{ $i }}" class="grid grid-cols-[1fr_9rem_auto] items-end gap-2">
                        <x-ui.select wire:model="structure.items.{{ $i }}.fee_head_id" :options="$headOptions" :placeholder="__('Fee head…')" />
                        <x-ui.input type="number" step="0.01" min="1" prefix="₹" wire:model="structure.items.{{ $i }}.amount" />
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeItem({{ $i }})"><span class="sr-only">{{ __('Remove') }}</span></x-ui.button>
                    </div>
                @endforeach
                <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="addItem">{{ __('Add amount') }}</x-ui.button>
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'structure-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="structure-form-el" loading="saveStructure">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
