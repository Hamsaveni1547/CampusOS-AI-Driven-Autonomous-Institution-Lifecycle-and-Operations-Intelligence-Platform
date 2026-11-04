<div>
    <x-ui.page-header :title="__('My mentees')" :description="__('Flags are reminders, not judgements: check with the student.')">
        <x-slot:actions><x-ui.button icon="calendar-plus" wire:click="openMeeting">{{ __('Schedule meeting') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.table :empty="$mentees === []">
                <x-slot:head>
                    <x-ui.th>{{ __('Student') }}</x-ui.th>
                    <x-ui.th>{{ __('Flags') }}</x-ui.th>
                </x-slot:head>
                @foreach ($mentees as $m)
                    <x-ui.tr wire:key="me-{{ $m['id'] }}">
                        <x-ui.td primary><a href="{{ route('mentees.show', $m['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $m['name'] }}</a><span class="block text-xs text-ink-subtle">{{ $m['register_no'] }} · {{ $m['section'] }}</span></x-ui.td>
                        <x-ui.td :label="__('Flags')">
                            @forelse ($m['reasons'] as $r)<x-ui.badge wire:key="mf-{{ $m['id'] }}-{{ $loop->index }}" size="sm" tone="warning" class="mb-1 mr-1">{{ $r }}</x-ui.badge>@empty<span class="text-xs text-success">{{ __('All good') }}</span>@endforelse
                        </x-ui.td>
                    </x-ui.tr>
                @endforeach
                <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No mentees assigned')" /></x-slot:emptyState>
            </x-ui.table>
        </div>

        <x-ui.card :title="__('Meetings')" :padding="false">
            @forelse ($meetings as $mt)
                <div wire:key="mm-{{ $mt['id'] }}" class="border-b border-line px-5 py-3 text-sm last:border-0">
                    <div class="flex items-center justify-between gap-2"><span class="font-medium text-ink">{{ $mt['agenda'] }}</span><x-ui.status-badge :status="$mt['status']" size="sm" /></div>
                    <p class="text-xs text-ink-muted">{{ collect($mt['students'])->pluck('name')->implode(', ') }}@if ($mt['scheduled_at']) · {{ \App\Support\Format::dateTime($mt['scheduled_at']) }}@endif</p>
                    @if ($mt['minutes'])<p class="mt-1 text-xs text-ink">{{ $mt['minutes'] }}</p>@endif
                    <div class="mt-2 flex gap-2">
                        @if ($mt['status'] === 'requested')
                            <x-ui.button size="sm" wire:click="openRecord(@js($mt), 'scheduled')">{{ __('Schedule') }}</x-ui.button>
                        @elseif ($mt['status'] === 'scheduled')
                            <x-ui.button size="sm" wire:click="openRecord(@js($mt), 'held')">{{ __('Record minutes') }}</x-ui.button>
                        @endif
                        @if (in_array($mt['status'], ['requested', 'scheduled'], true))
                            <x-ui.button size="sm" variant="ghost" wire:click="openRecord(@js($mt), 'cancelled')">{{ __('Cancel') }}</x-ui.button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No meetings yet.') }}</p>
            @endforelse
        </x-ui.card>
    </div>

    <x-ui.modal name="schedule-meeting" :title="__('Schedule meeting')">
        <form wire:submit="schedule" id="schedule-meeting-form" class="space-y-4">
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($menteeOptions as $id => $name)
                    <x-ui.checkbox wire:key="sm-{{ $id }}" wire:model="meeting.student_ids" value="{{ $id }}" :label="$name" />
                @endforeach
            </div>
            @error('meeting.student_ids')<p class="text-sm text-danger">{{ $message }}</p>@enderror
            <x-ui.input type="datetime-local" :label="__('When')" wire:model="meeting.scheduled_at" required />
            <x-ui.input :label="__('Agenda')" wire:model="meeting.agenda" required />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'schedule-meeting')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="schedule-meeting-form" loading="schedule">{{ __('Schedule') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="record-meeting" :title="['scheduled' => __('Schedule the meeting'), 'held' => __('Record the meeting'), 'cancelled' => __('Cancel the meeting')][$record['status']] ?? ''" size="sm">
        <form wire:submit="saveRecord" id="record-meeting-form" class="space-y-4">
            @if ($record['status'] === 'scheduled')
                <x-ui.input type="datetime-local" :label="__('When')" wire:model="record.scheduled_at" required />
            @elseif ($record['status'] === 'held')
                <x-ui.textarea :label="__('Minutes')" wire:model="record.minutes" rows="3" required />
                <p class="text-sm font-medium text-ink">{{ __('Attended') }}</p>
                @foreach ($record['students'] as $s)
                    <x-ui.checkbox wire:key="ra-{{ $s['id'] }}" wire:model="record.attended_ids" value="{{ $s['id'] }}" :label="$s['name']" />
                @endforeach
            @else
                <p class="text-sm text-ink-muted">{{ __('Cancel this meeting?') }}</p>
            @endif
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'record-meeting')">{{ __('Back') }}</x-ui.button>
            <x-ui.button type="submit" form="record-meeting-form" loading="saveRecord">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
