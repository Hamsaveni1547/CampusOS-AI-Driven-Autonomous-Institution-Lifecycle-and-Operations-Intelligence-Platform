<div>
    <x-ui.page-header :title="__('My mentor')" :description="__('Your mentor is your first point of contact for academic or personal guidance.')" />

    @if (! $data['mentor'])
        <x-ui.empty-state icon="user-check" :title="__('No mentor assigned yet')" />
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card :title="$data['mentor']['name']">
                <p class="text-sm text-ink-muted">{{ $data['mentor']['designation'] }}@if ($data['mentor']['department']) · {{ $data['mentor']['department'] }}@endif</p>
                <p class="mt-1 text-sm text-ink">{{ $data['mentor']['email'] }}</p>
                <p class="mt-1 text-xs text-ink-subtle">{{ __('Your mentor since :d', ['d' => \App\Support\Format::date($data['mentor']['since'])]) }}</p>
                <form wire:submit="request" class="mt-4 space-y-2 border-t border-line pt-4">
                    <x-ui.input :label="__('Ask for a meeting')" wire:model="agenda" :placeholder="__('What would you like to discuss?')" />
                    @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
                    <x-ui.button type="submit" size="sm" icon="send" loading="request">{{ __('Send request') }}</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card class="lg:col-span-2" :title="__('Meetings')" :padding="false">
                @forelse ($data['meetings'] as $mt)
                    <div wire:key="ym-{{ $mt['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                        <span class="min-w-0 flex-1 text-ink">{{ $mt['agenda'] }}</span>
                        <span class="text-xs text-ink-muted">{{ $mt['scheduled_at'] ? \App\Support\Format::dateTime($mt['scheduled_at']) : __('waiting for a time') }}</span>
                        <x-ui.status-badge :status="$mt['status']" size="sm" />
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No meetings yet.') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    @endif
</div>
