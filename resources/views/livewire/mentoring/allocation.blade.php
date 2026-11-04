<div>
    <x-ui.page-header :title="__('Mentoring')" :description="__('Assign mentors by section, and see students who may need support.')" />

    <div class="mb-6 flex gap-5 border-b border-line">
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'allocation')" :active="$tab === 'allocation'">{{ __('Mentors') }}</x-ui.tab-link>
        <x-ui.tab-link href="#" wire:click.prevent="$set('tab', 'risk')" :active="$tab === 'risk'">{{ __('At risk') }}</x-ui.tab-link>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <div class="w-56"><x-ui.select wire:model.live="batch" :options="$batches" :placeholder="__('Choose batch…')" /></div>
        <div class="w-32"><x-ui.select wire:model.live="section" :options="$sections" :placeholder="$tab === 'risk' ? __('All sections') : __('Section…')" /></div>
    </div>

    @if ($tab === 'allocation')
        @if ($section === '')
            <x-ui.empty-state icon="users" :title="__('Choose a section')" />
        @else
            @if ($canAllocate)
                <x-ui.card class="mb-6" :title="__('Share unassigned students among mentors')">
                    <form wire:submit="allocate" class="space-y-3">
                        <div class="grid gap-2 sm:grid-cols-3">
                            @foreach ($teachers as $t)
                                <x-ui.checkbox wire:key="mt-{{ $t['value'] }}" wire:model="mentorIds" value="{{ $t['value'] }}" :label="$t['label']" :description="$t['description']" />
                            @endforeach
                        </div>
                        @error('mentorIds')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                        <x-ui.button type="submit" icon="shuffle" loading="allocate">{{ __('Allocate') }}</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.table :empty="$students === []">
                <x-slot:head>
                    <x-ui.th>{{ __('Student') }}</x-ui.th>
                    <x-ui.th>{{ __('Mentor') }}</x-ui.th>
                    <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
                </x-slot:head>
                @foreach ($students as $s)
                    <x-ui.tr wire:key="ms-{{ $s['id'] }}">
                        <x-ui.td primary><span class="mr-2 font-mono text-xs text-ink-muted">{{ $s['roll_no'] ?? '—' }}</span><a href="{{ route('mentees.show', $s['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $s['name'] }}</a><span class="block font-mono text-xs text-ink-subtle">{{ $s['register_no'] }}</span></x-ui.td>
                        <x-ui.td :label="__('Mentor')">{{ $s['mentor']['name'] ?? '—' }}@if ($s['mentor'])<span class="block text-xs text-ink-muted">{{ __('since :d', ['d' => \App\Support\Format::date($s['mentor']['since'])]) }}</span>@endif</x-ui.td>
                        <x-ui.td class="text-right">@if ($canAllocate)<x-ui.button size="sm" variant="ghost" wire:click="openReassign('{{ $s['id'] }}', @js($s['name']))">{{ __('Change') }}</x-ui.button>@endif</x-ui.td>
                    </x-ui.tr>
                @endforeach
                <x-slot:emptyState><x-ui.empty-state compact icon="users" :title="__('No students in this section')" /></x-slot:emptyState>
            </x-ui.table>
        @endif
    @else
        <x-ui.table :empty="$atRisk === []">
            <x-slot:head>
                <x-ui.th>{{ __('Student') }}</x-ui.th>
                <x-ui.th>{{ __('Why') }}</x-ui.th>
                <x-ui.th>{{ __('Mentor') }}</x-ui.th>
            </x-slot:head>
            @foreach ($atRisk as $s)
                <x-ui.tr wire:key="ar-{{ $s['id'] }}">
                    <x-ui.td primary><a href="{{ route('mentees.show', $s['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $s['name'] }}</a><span class="block text-xs text-ink-subtle">{{ $s['register_no'] }} · {{ $s['section'] }}</span></x-ui.td>
                    <x-ui.td :label="__('Why')">@foreach ($s['reasons'] as $r)<x-ui.badge wire:key="arr-{{ $s['id'] }}-{{ $loop->index }}" size="sm" tone="warning" class="mb-1 mr-1">{{ $r }}</x-ui.badge>@endforeach</x-ui.td>
                    <x-ui.td :label="__('Mentor')">{{ $s['mentor'] ?? __('Not assigned') }}</x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="circle-check" :title="__('No students flagged')" /></x-slot:emptyState>
        </x-ui.table>
    @endif

    <x-ui.modal name="reassign-mentor" :title="__('Change mentor of :n', ['n' => $reassign['name']])" size="sm">
        <form wire:submit="saveReassign" id="reassign-mentor-form" class="space-y-4">
            <x-ui.select :label="__('New mentor')" wire:model="reassign.mentor_id" :options="collect($teachers)->mapWithKeys(fn ($t) => [$t['value'] => $t['label']])->all()" :placeholder="__('Choose…')" required />
            <x-ui.input :label="__('Reason')" wire:model="reassign.reason" optional />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reassign-mentor')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reassign-mentor-form" loading="saveReassign">{{ __('Change') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
