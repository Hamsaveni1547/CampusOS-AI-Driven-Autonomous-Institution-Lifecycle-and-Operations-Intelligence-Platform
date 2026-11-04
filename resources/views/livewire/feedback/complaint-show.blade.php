<div>
    <x-ui.page-header :title="$c['subject']" :description="$c['category']['name'].' · '.__('raised :d', ['d' => \App\Support\Format::dateTime($c['raised_at'])])" :back="$c['can_handle'] ? route('complaints.index') : route('my-complaints.index')" :back-label="$c['can_handle'] ? __('Complaints') : __('My complaints')">
        <x-slot:meta><x-ui.status-badge :status="$c['overdue'] ? 'overdue' : $c['status']" /></x-slot:meta>
        @if ($c['can_handle'] && in_array($c['status'], ['open', 'in_progress'], true))
            <x-slot:actions><x-ui.button variant="secondary" icon="user-check" wire:click="assignToMe">{{ __('Assign to me') }}</x-ui.button></x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card class="mb-6">
        <p class="whitespace-pre-line text-sm text-ink">{{ $c['description'] }}</p>
        <x-attachments :files="$c['attachments'] ?? []" class="mt-3" />
        <p class="mt-3 text-xs text-ink-muted">
            {{ $c['can_handle'] ? ($c['is_anonymous'] ? __('Raised anonymously') : __('Raised by :n', ['n' => $c['raised_by']])) : ($c['is_anonymous'] ? __('Your name is hidden from staff.') : '') }}
            @if ($c['assigned_to']) · {{ __('With :n', ['n' => $c['assigned_to']]) }}@endif
            @if ($c['due_at'] && in_array($c['status'], ['open', 'in_progress'], true)) · {{ __('due :d', ['d' => \App\Support\Format::dateTime($c['due_at'])]) }}@endif
            @if ($c['rating']) · {{ __('Rated :r / 5', ['r' => $c['rating']]) }}@endif
        </p>
    </x-ui.card>

    <div class="mb-6 space-y-3">
        @foreach ($c['messages'] as $m)
            @php($ownSide = $m['is_staff'] === $c['can_handle'])
            <div wire:key="cm-{{ $loop->index }}" @class(['max-w-2xl rounded-lg px-4 py-3 text-sm', 'ml-auto bg-primary-soft' => $ownSide, 'bg-surface-muted' => ! $ownSide, 'border border-dashed border-warning' => $m['is_internal']])>
                <p class="mb-1 text-xs text-ink-muted">{{ $m['from'] }} · {{ \App\Support\Format::dateTime($m['at']) }}@if ($m['is_internal']) · <span class="text-warning">{{ __('internal note') }}</span>@endif</p>
                <p class="whitespace-pre-line text-ink">{{ $m['body'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($c['status'] !== 'closed')
        <x-ui.card>
            <form wire:submit="send" class="space-y-3">
                <x-ui.textarea :label="$c['can_handle'] ? __('Reply') : __('Add a message')" wire:model="body" rows="3" required />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
                <div class="flex flex-wrap items-center gap-2">
                    @if ($c['can_handle'])
                        <x-ui.checkbox wire:model="internal" :label="__('Internal note (not shown to the complainant)')" />
                        <span class="flex-1"></span>
                        <x-ui.button type="submit" variant="secondary" loading="send">{{ __('Send') }}</x-ui.button>
                        <x-ui.button icon="check" wire:click="send(true)">{{ __('Send and resolve') }}</x-ui.button>
                    @else
                        <x-ui.button type="submit" loading="send">{{ $c['status'] === 'resolved' ? __('Not solved — reopen') : __('Send') }}</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        @if (! $c['can_handle'] && $c['status'] === 'resolved')
            <x-ui.card class="mt-4" :title="__('Is it solved?')">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="w-40"><x-ui.select :label="__('Rating')" wire:model="rating" :options="[5 => '5 — very satisfied', 4 => '4', 3 => '3', 2 => '2', 1 => '1 — not satisfied']" /></div>
                    <x-ui.button icon="check" wire:click="close">{{ __('Close complaint') }}</x-ui.button>
                </div>
            </x-ui.card>
        @endif
    @endif
</div>
