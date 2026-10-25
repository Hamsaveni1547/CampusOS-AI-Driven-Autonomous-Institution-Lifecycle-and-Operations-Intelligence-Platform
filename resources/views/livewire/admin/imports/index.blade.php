@php($sampleCsv = $columns ? 'data:text/csv;charset=utf-8,'.rawurlencode(implode(',', [...$columns['required'], ...$columns['optional']])."\n") : null)
<div>
    <x-ui.page-header :title="__('Imports')" :description="__('Add many records at once from a CSV file. Every row is checked before anything is saved.')">
        <x-slot:actions>
            @if ($types !== [])
                <x-ui.button icon="upload" wire:click="openStart">{{ __('New import') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table loading="gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th>{{ __('Type') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th>{{ __('Rows') }}</x-ui.th>
            <x-ui.th>{{ __('By') }}</x-ui.th>
            <x-ui.th>{{ __('Started') }}</x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $import)
            <x-ui.tr wire:key="i-{{ $import['id'] }}">
                <x-ui.td primary><button type="button" wire:click="show('{{ $import['id'] }}')" class="font-medium text-ink hover:underline">{{ $types[$import['type']] ?? \App\Support\Format::label($import['type']) }}</button></x-ui.td>
                <x-ui.td :label="__('Status')"><x-ui.status-badge :status="$import['status']" /></x-ui.td>
                <x-ui.td :label="__('Rows')">{{ \App\Support\Format::number($import['processed_rows'] ?: $import['valid_rows']) }} / {{ \App\Support\Format::number($import['total_rows']) }}@if ($import['failed_rows']) <span class="text-danger">· {{ __(':count failed', ['count' => $import['failed_rows']]) }}</span>@endif</x-ui.td>
                <x-ui.td :label="__('By')">{{ $import['created_by']['name'] ?? '—' }}</x-ui.td>
                <x-ui.td :label="__('Started')"><x-ui.time :value="$import['created_at']" /></x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="file-up" :title="__('No imports yet')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="import-start" :title="__('New import')">
        <form wire:submit="start" id="import-start-form" class="space-y-4">
            <x-ui.select :label="__('What are you importing?')" wire:model.live="type" :options="$types" required />
            @if ($columns)
                <x-ui.alert tone="info">
                    <p>{{ __('Required columns') }}: <span class="font-mono">{{ implode(', ', $columns['required']) }}</span></p>
                    @if ($columns['optional'])<p class="mt-1">{{ __('Optional') }}: <span class="font-mono">{{ implode(', ', $columns['optional']) }}</span></p>@endif
                    <a href="{{ $sampleCsv }}" download="{{ $type }}-template.csv" class="mt-2 inline-block font-medium underline">{{ __('Download blank template') }}</a>
                </x-ui.alert>
            @endif
            <x-ui.file-upload :label="__('CSV file')" wire:model="file" accept=".csv,text/csv" :max-kb="10240" />
            @if ($type !== '')
                <x-ui.switch wire:model="sendInvites" :label="__('Email an invitation to each new account')" />
            @endif
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'import-start')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="import-start-form" loading="start">{{ __('Upload and check') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.drawer name="import-detail" size="lg" :title="$open ? ($types[$open['type']] ?? \App\Support\Format::label($open['type'])).' '.__('import') : ''">
        @if ($open)
            <div @if ($busy) wire:poll.2s @endif class="space-y-5">
                <div class="flex items-center gap-3">
                    <x-ui.status-badge :status="$open['status']" />
                    @if ($busy)<x-ui.spinner /><span class="text-sm text-ink-muted">{{ __('Working… this updates by itself.') }}</span>@endif
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <x-ui.stat :label="__('Rows')" :value="\App\Support\Format::number($open['total_rows'])" />
                    <x-ui.stat :label="__('Valid')" :value="\App\Support\Format::number($open['valid_rows'])" tone="success" />
                    <x-ui.stat :label="__('Added')" :value="\App\Support\Format::number($open['processed_rows'])" tone="info" />
                </div>
                @if ($open['status'] === 'processing' && $open['valid_rows'])
                    <x-ui.progress :value="(int) round($open['processed_rows'] * 100 / $open['valid_rows'])" show-value />
                @endif
                @if ($open['failure_message'])<x-ui.alert tone="danger">{{ $open['failure_message'] }}</x-ui.alert>@endif
                @if ($open['status'] === 'validated')
                    <x-ui.alert tone="success">{{ __('All rows passed the checks. Commit to add them.') }}</x-ui.alert>
                @elseif ($open['status'] === 'invalid')
                    <x-ui.alert tone="warning">{{ __('Some rows have problems. Fix the file and upload it again.') }}</x-ui.alert>
                @endif

                @if ($open['errors'] !== [])
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-ink">{{ __('Problems') }}</h3>
                        <div class="max-h-96 divide-y divide-line overflow-y-auto rounded-lg border border-line">
                            @foreach ($open['errors'] as $error)
                                <div wire:key="err-{{ $loop->index }}" class="flex gap-3 px-4 py-2 text-sm">
                                    <span class="w-16 shrink-0 font-mono text-ink-muted">{{ __('Row :n', ['n' => $error['row'] ?? '?']) }}</span>
                                    <span class="text-ink">{{ collect($error['errors'] ?? [])->flatten()->implode(' ') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
        <x-slot:footer>
            @if ($open && in_array($open['status'], ['pending', 'validating', 'validated', 'invalid'], true))
                <x-ui.button variant="ghost" class="mr-auto" wire:click="cancel" loading="cancel">{{ __('Cancel import') }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'import-detail')">{{ __('Close') }}</x-ui.button>
            @if ($open && $open['status'] === 'validated')
                <x-ui.button wire:click="commit" loading="commit">{{ __('Commit :count rows', ['count' => $open['valid_rows']]) }}</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.drawer>
</div>
