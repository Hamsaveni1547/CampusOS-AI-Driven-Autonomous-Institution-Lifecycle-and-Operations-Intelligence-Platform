<div>
    <x-ui.page-header :title="__('My leave')" :description="__('Balances for :y. Sundays and holidays are not counted.', ['y' => $data['year']])">
        <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Apply for leave') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($data['balances'] as $b)
            <x-ui.card wire:key="lb-{{ $b['type']['id'] }}">
                <p class="text-sm text-ink-muted">{{ $b['type']['name'] }}</p>
                @if ($b['available'] !== null)
                    <p class="text-2xl font-semibold text-ink">{{ $b['available'] + 0 }} <span class="text-sm font-normal text-ink-muted">/ {{ $b['allotted'] + 0 }}</span></p>
                    <p class="text-xs text-ink-subtle">{{ __(':u used · :p waiting', ['u' => $b['used'] + 0, 'p' => $b['pending'] + 0]) }}</p>
                @else
                    <p class="text-2xl font-semibold text-ink">{{ $b['used'] + 0 }}</p>
                    <p class="text-xs text-ink-subtle">{{ __('days taken (unpaid)') }}</p>
                @endif
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card :title="__('Applications')" :padding="false">
        @forelse ($data['applications'] as $a)
            <div wire:key="la-{{ $a['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-ink">{{ $a['type']['name'] }} · {{ \App\Support\Format::date($a['from_date']) }}@if ($a['to_date'] !== $a['from_date']) – {{ \App\Support\Format::date($a['to_date']) }}@endif</p>
                    <p class="text-xs text-ink-muted">{{ $a['half_day'] ? __('Half day') : __(':d day(s)', ['d' => $a['days'] + 0]) }} · {{ $a['reason'] }}@if ($a['remarks']) · <span class="text-danger">{{ $a['remarks'] }}</span>@endif</p>
                </div>
                <x-ui.status-badge :status="$a['status']" size="sm" />
                @if ($a['can_cancel'])
                    <x-ui.button size="sm" variant="ghost" wire:click="cancel('{{ $a['id'] }}')" wire:confirm="{{ __('Cancel this leave?') }}">{{ __('Cancel') }}</x-ui.button>
                @endif
            </div>
        @empty
            <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No leave applied yet.') }}</p>
        @endforelse
    </x-ui.card>

    <x-ui.modal name="apply-leave" :title="__('Apply for leave')">
        <form wire:submit="apply" id="apply-leave-form" class="grid gap-4 sm:grid-cols-2">
            <x-ui.select :label="__('Leave type')" wire:model.live="form.leave_type_id" :options="$types" :placeholder="__('Choose…')" required class="sm:col-span-2" />
            <x-ui.input type="date" :label="__('From')" wire:model.live="form.from_date" required />
            <x-ui.input type="date" :label="__('To')" wire:model.live="form.to_date" required />
            @if (in_array($form['leave_type_id'], $halfDayTypes, true) && $form['from_date'] === $form['to_date'])
                <x-ui.switch wire:model="form.half_day" :label="__('Half day')" class="sm:col-span-2" />
            @endif
            <x-ui.textarea :label="__('Reason')" wire:model="form.reason" rows="2" required class="sm:col-span-2" />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'apply-leave')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="apply-leave-form" loading="apply">{{ __('Apply') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
