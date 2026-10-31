<div>
    <x-ui.page-header :title="__('Term registrations')" :description="__('Students’ course registrations for the term. Approve them so they appear in class rosters.')">
        <x-slot:actions>
            @if ($selected !== [])
                <x-ui.button variant="secondary" wire:click="askReject">{{ __('Reject :n', ['n' => count($selected)]) }}</x-ui.button>
                <x-ui.button icon="circle-check" wire:click="approve" loading="approve">{{ __('Approve :n', ['n' => count($selected)]) }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filters search="search" :search-placeholder="__('Name or register number…')" :active="$chips">
        <x-ui.select wire:model.live="filters.term" :options="$terms" :placeholder="__('Current term')" class="sm:w-56" :aria-label="__('Term')" />
        <x-ui.select wire:model.live="filters.programme" :options="$programmes" :placeholder="__('All programmes')" class="sm:w-56" :aria-label="__('Programme')" />
        <x-ui.select wire:model.live="filters.status" :options="$statuses" :placeholder="__('Any status')" class="sm:w-36" :aria-label="__('Status')" />
    </x-ui.filters>

    <x-ui.table loading="search,filters,gotoPage,setPerPage" :empty="$rows->isEmpty()">
        <x-slot:head>
            <x-ui.th><span class="sr-only">{{ __('Select') }}</span></x-ui.th>
            <x-ui.th>{{ __('Student') }}</x-ui.th>
            <x-ui.th>{{ __('Section') }}</x-ui.th>
            <x-ui.th>{{ __('Courses') }}</x-ui.th>
            <x-ui.th>{{ __('Credits') }}</x-ui.th>
            <x-ui.th>{{ __('Status') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($rows->items as $registration)
            <x-ui.tr wire:key="tr-{{ $registration['id'] }}">
                <x-ui.td>
                    @if ($registration['status'] === 'submitted')
                        <input type="checkbox" value="{{ $registration['id'] }}" wire:model.live="selected" class="size-4 rounded border-line-strong text-primary focus:ring-primary" aria-label="{{ __('Select') }}">
                    @endif
                </x-ui.td>
                <x-ui.td primary>
                    <a href="{{ route('students.show', $registration['student']['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $registration['student']['name'] }}</a>
                    <p class="font-mono text-xs text-ink-muted">{{ $registration['student']['register_no'] }}</p>
                </x-ui.td>
                <x-ui.td :label="__('Section')">{{ $registration['section']['batch'] }} · {{ $registration['section']['name'] }}<span class="block text-xs text-ink-muted">{{ __('Term :n', ['n' => $registration['term_number']]) }}</span></x-ui.td>
                <x-ui.td :label="__('Courses')">
                    <div class="flex flex-wrap gap-1">
                        @foreach ($registration['courses'] as $course)
                            <x-ui.badge size="sm" :tone="$course['type'] === 'elective' ? 'info' : 'neutral'">{{ $course['code'] }}</x-ui.badge>
                        @endforeach
                    </div>
                </x-ui.td>
                <x-ui.td :label="__('Credits')">{{ $registration['total_credits'] }}</x-ui.td>
                <x-ui.td :label="__('Status')">
                    <x-ui.status-badge :status="$registration['status']" :label="$statuses[$registration['status']] ?? null" />
                    @if ($registration['remarks'])<p class="mt-1 text-xs text-ink-muted">{{ $registration['remarks'] }}</p>@endif
                </x-ui.td>
                <x-ui.td class="text-right">
                    @if ($registration['status'] === 'submitted')
                        <x-ui.button size="sm" variant="ghost" icon="check" wire:click="approve(['{{ $registration['id'] }}'])" :aria-label="__('Approve')" />
                        <x-ui.button size="sm" variant="ghost" icon="x" wire:click="askReject('{{ $registration['id'] }}')" :aria-label="__('Reject')" />
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="clipboard-list" :title="__('No registrations here')" /></x-slot:emptyState>
        <x-slot:footer><x-ui.pagination :page="$rows" /></x-slot:footer>
    </x-ui.table>

    <x-ui.modal name="reject-registrations" :title="__('Reject registration')" size="sm">
        <form wire:submit="reject" id="reject-registrations-form">
            <x-ui.textarea :label="__('Reason')" wire:model="remarks" rows="2" required :hint="__('The student sees this and can submit again.')" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reject-registrations')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reject-registrations-form" variant="danger" loading="reject">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
