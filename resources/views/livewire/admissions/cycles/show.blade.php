<div>
    <x-ui.page-header :title="$cycle['name']" :description="($cycle['academic_year']['name'] ?? '').' · '.\App\Support\Format::date($cycle['opens_on']).' – '.\App\Support\Format::date($cycle['closes_on'])" :back="route('admission-cycles.index')" :back-label="__('Admission cycles')">
        <x-slot:meta>
            <x-ui.status-badge :status="$cycle['status']" />
            @if ($cycle['is_accepting'])<x-ui.badge size="sm" tone="success" dot>{{ __('Accepting applications') }}</x-ui.badge>@endif
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="inbox" :href="route('applications.index', ['f' => ['cycle' => $cycle['id']]])">{{ __('Applications') }}</x-ui.button>
            @if ($cycle['status'] === 'open')
                <x-ui.button variant="secondary" icon="lock" wire:click="setOpen(false)" wire:confirm="{{ __('Close this cycle? No new applications will be accepted.') }}">{{ __('Close cycle') }}</x-ui.button>
            @elseif ($cycle['status'] === 'draft')
                <x-ui.button icon="circle-check" wire:click="setOpen(true)" loading="setOpen">{{ __('Open for applications') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat icon="armchair" :label="__('Seats')" :value="\App\Support\Format::number($totals['seats'])" />
        <x-ui.stat icon="user-check" :label="__('Offered or admitted')" :value="\App\Support\Format::number($totals['filled'])" tone="success" />
        <x-ui.stat icon="inbox" :label="__('Applications')" :value="\App\Support\Format::number($cycle['applications_count'] ?? 0)" tone="info" />
    </div>

    <x-ui.card :title="__('Seat matrix')" :description="__('Seats per programme and quota. Offers stop when a row is full.')" :padding="false">
        @if ($cycle['status'] !== 'closed')
            <x-slot:actions><x-ui.button size="sm" icon="plus" wire:click="editSeat">{{ __('Add seats') }}</x-ui.button></x-slot:actions>
        @endif
        <x-ui.table :empty="$cycle['seats'] === []">
            <x-slot:head>
                <x-ui.th>{{ __('Programme') }}</x-ui.th>
                <x-ui.th>{{ __('Quota') }}</x-ui.th>
                <x-ui.th>{{ __('Seats') }}</x-ui.th>
                <x-ui.th>{{ __('Min. %') }}</x-ui.th>
                <x-ui.th>{{ __('Admission fee') }}</x-ui.th>
                <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
            </x-slot:head>
            @foreach ($cycle['seats'] as $seat)
                <x-ui.tr wire:key="seat-{{ $seat['id'] }}">
                    <x-ui.td primary>{{ $seat['programme']['name'] ?? '—' }}</x-ui.td>
                    <x-ui.td :label="__('Quota')">
                        {{ $quotas[$seat['quota']] ?? \App\Support\Format::label($seat['quota']) }}
                        @if ($seat['needs_approval'])<x-ui.badge size="sm" tone="warning">{{ __('Needs approval') }}</x-ui.badge>@endif
                    </x-ui.td>
                    <x-ui.td :label="__('Seats')">
                        <div class="w-36">
                            <p class="mb-1 text-sm">{{ $seat['filled'] }} / {{ $seat['total'] }}</p>
                            <x-ui.progress :value="$seat['total'] > 0 ? (int) round($seat['filled'] * 100 / $seat['total']) : 0" :tone="$seat['available'] === 0 ? 'danger' : 'primary'" />
                        </div>
                    </x-ui.td>
                    <x-ui.td :label="__('Min. %')">{{ $seat['min_percentage'] ?? '—' }}</x-ui.td>
                    <x-ui.td :label="__('Admission fee')">₹{{ \App\Support\Format::number($seat['admission_fee']) }}</x-ui.td>
                    <x-ui.td class="text-right">
                        @if ($cycle['status'] !== 'closed')
                            <x-ui.button size="sm" variant="ghost" icon="pencil" wire:click="editSeat('{{ $seat['id'] }}')" :aria-label="__('Edit')" />
                            <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="askDeleteSeat('{{ $seat['id'] }}')" :aria-label="__('Remove')" />
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="armchair" :title="__('No seats yet')" :description="__('Add seats before opening the cycle.')" /></x-slot:emptyState>
        </x-ui.table>
    </x-ui.card>

    @if ($cycle['required_documents'] !== [] || $cycle['instructions'])
        <x-ui.card class="mt-6" :title="__('For applicants')">
            @if ($cycle['required_documents'] !== [])
                <div class="mb-3 flex flex-wrap gap-1.5">
                    @foreach ($cycle['required_documents'] as $type)<x-ui.badge>{{ \App\Support\Format::label($type) }}</x-ui.badge>@endforeach
                </div>
            @endif
            @if ($cycle['instructions'])<p class="whitespace-pre-line text-sm text-ink-muted">{{ $cycle['instructions'] }}</p>@endif
        </x-ui.card>
    @endif

    <x-ui.modal name="seat-form" :title="$seatId ? __('Edit seats') : __('Add seats')">
        <form wire:submit="saveSeat" id="seat-form" class="space-y-4">
            @unless ($seatId)
                <x-ui.combobox :label="__('Programme')" wire:model="seatForm.programme_id" :options="$programmes" required />
                <x-ui.select :label="__('Quota')" wire:model="seatForm.quota" :options="$quotas" :placeholder="__('Choose')" required />
            @endunless
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input :label="__('Seats')" type="number" min="1" wire:model="seatForm.total" required />
                <x-ui.input :label="__('Min. %')" type="number" min="0" max="100" step="0.01" wire:model="seatForm.min_percentage" optional />
                <x-ui.input :label="__('Admission fee')" type="number" min="0" step="0.01" wire:model="seatForm.admission_fee" prefix="₹" optional />
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'seat-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="seat-form" loading="saveSeat">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-dialog name="delete-seat" :title="__('Remove this seat row?')" :confirm-label="__('Remove')" action="deleteSeat">
        {{ __('Only rows without applications can be removed.') }}
    </x-ui.confirm-dialog>
</div>
