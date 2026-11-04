@php($money = fn ($v) => '₹'.number_format((float) $v, 0))
<div>
    <x-ui.page-header :title="$m['name']" :description="$m['register_no'].' · '.($m['programme'] ?? '').' · '.($m['section'] ?? '')" :back="route('my-mentees.index')" :back-label="__('My mentees')">
        <x-slot:meta>@foreach ($m['flags'] as $f)<x-ui.badge wire:key="fl-{{ $loop->index }}" size="sm" tone="warning">{{ $f }}</x-ui.badge>@endforeach</x-slot:meta>
        <x-slot:actions><x-ui.button variant="secondary" icon="flag" wire:click="openConcern">{{ __('Raise concern') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat :label="__('CGPA')" :value="$m['result']['cgpa'] ?? '—'" icon="award" :hint="$m['result'] ? __(':b backlog(s) · :c', ['b' => $m['result']['backlogs'], 'c' => $m['result']['cycle']]) : null" />
        <x-ui.stat :label="__('Fee balance')" :value="$money($m['fees']['balance'])" icon="wallet" :tone="$m['fees']['overdue'] > 0 ? 'danger' : 'success'" :hint="$m['fees']['overdue'] > 0 ? __(':a overdue', ['a' => $money($m['fees']['overdue'])]) : null" />
        <x-ui.stat :label="__('Mentor')" :value="$m['mentor']['name'] ?? '—'" icon="user-check" :hint="$m['mentor'] ? __('since :d', ['d' => \App\Support\Format::date($m['mentor']['since'])]) : null" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card :title="__('Concerns')" :padding="false">
                @forelse ($m['concerns'] as $c)
                    <div wire:key="cn-{{ $c['id'] }}" class="border-b border-line px-5 py-3 text-sm last:border-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-ink">{{ $categories[$c['category']] ?? $c['category'] }}: {{ $c['description'] }}</span>
                            <x-ui.status-badge :status="$c['overdue'] ? 'overdue' : $c['status']" size="sm" />
                        </div>
                        @if ($c['action_plan'])<p class="text-xs text-ink-muted">{{ __('Plan: :p', ['p' => $c['action_plan']]) }}@if ($c['review_on']) · {{ __('review :d', ['d' => \App\Support\Format::date($c['review_on'])]) }}@endif</p>@endif
                        @if ($c['outcome'])<p class="text-xs text-success">{{ __('Outcome: :o', ['o' => $c['outcome']]) }}</p>@endif
                        @if ($c['status'] === 'open')<x-ui.button class="mt-2" size="sm" variant="ghost" icon="check" wire:click="openResolve('{{ $c['id'] }}')">{{ __('Resolve') }}</x-ui.button>@endif
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No concerns recorded.') }}</p>
                @endforelse
            </x-ui.card>

            <x-ui.card :title="__('Notes')">
                <form wire:submit="addNote" class="mb-4 space-y-2">
                    <x-ui.textarea wire:model="note.body" rows="2" :placeholder="__('Add a note about this mentee…')" />
                    <div class="flex items-center gap-3">
                        <div class="w-56"><x-ui.select wire:model="note.visibility" :options="['shared' => __('Shared with HOD'), 'confidential' => __('Confidential (me and counsellor)')]" /></div>
                        <x-ui.button type="submit" size="sm" loading="addNote">{{ __('Add note') }}</x-ui.button>
                    </div>
                </form>
                @forelse ($m['notes'] as $n)
                    <div wire:key="nt-{{ $n['id'] }}" class="border-t border-line py-2 text-sm">
                        <p class="whitespace-pre-line text-ink">{{ $n['body'] }}</p>
                        <p class="text-xs text-ink-muted">{{ $n['by'] }} · {{ \App\Support\Format::dateTime($n['at']) }}@if ($n['visibility'] === 'confidential') · <span class="text-warning">{{ __('confidential') }}</span>@endif</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('No notes yet.') }}</p>
                @endforelse
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card :title="__('Attendance this term')" :padding="false">
                @forelse ($m['attendance'] as $a)
                    <div wire:key="at-{{ $loop->index }}" class="flex items-center justify-between gap-2 border-b border-line px-5 py-2 text-sm last:border-0">
                        <span class="text-ink">{{ $a['course']['code'] }}</span>
                        <span class="flex items-center gap-2"><span class="text-ink-muted">{{ $a['percentage'] !== null ? $a['percentage'].'%' : '—' }}</span><x-attendance.standing :standing="$a['standing']" /></span>
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No attendance yet.') }}</p>
                @endforelse
            </x-ui.card>

            <x-ui.card :title="__('Contact')">
                <p class="text-sm text-ink">{{ $m['email'] }}@if ($m['phone']) · {{ $m['phone'] }}@endif</p>
                @foreach ($m['guardians'] as $g)<p wire:key="gd-{{ $loop->index }}" class="mt-1 text-xs text-ink-muted">{{ \App\Support\Format::label($g['relation']) }}: {{ $g['name'] }} · {{ $g['phone'] }}</p>@endforeach
            </x-ui.card>

            <x-ui.card :title="__('Meetings')" :padding="false">
                @forelse ($m['meetings'] as $mt)
                    <div wire:key="mtg-{{ $mt['id'] }}" class="border-b border-line px-5 py-2 text-sm last:border-0">
                        <p class="flex justify-between"><span class="text-ink">{{ $mt['agenda'] }}</span><x-ui.status-badge :status="$mt['status']" size="sm" /></p>
                        @if ($mt['scheduled_at'])<p class="text-xs text-ink-muted">{{ \App\Support\Format::dateTime($mt['scheduled_at']) }}</p>@endif
                        @if ($mt['minutes'])<p class="text-xs text-ink">{{ $mt['minutes'] }}</p>@endif
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No meetings yet.') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>

    <x-ui.modal name="add-concern" :title="__('Raise a concern')">
        <form wire:submit="addConcern" id="add-concern-form" class="grid gap-4 sm:grid-cols-2">
            <x-ui.select :label="__('Kind')" wire:model="concern.category" :options="$categories" required />
            <x-ui.input type="date" :label="__('Review on')" wire:model="concern.review_on" optional />
            <x-ui.textarea :label="__('What is the concern?')" wire:model="concern.description" rows="2" required class="sm:col-span-2" />
            <x-ui.textarea :label="__('Action plan')" wire:model="concern.action_plan" rows="2" optional class="sm:col-span-2" />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'add-concern')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="add-concern-form" loading="addConcern">{{ __('Save') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="resolve-concern" :title="__('Resolve concern')" size="sm">
        <form wire:submit="resolve" id="resolve-concern-form"><x-ui.textarea :label="__('Outcome')" wire:model="resolution.outcome" rows="2" required /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'resolve-concern')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="resolve-concern-form" loading="resolve">{{ __('Resolve') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
