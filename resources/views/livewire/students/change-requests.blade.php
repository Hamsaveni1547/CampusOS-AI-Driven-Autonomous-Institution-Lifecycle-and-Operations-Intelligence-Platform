<div>
    <x-ui.page-header :title="__('Correction requests')" :description="__('Students asking to correct details they cannot edit themselves.')" />

    <div class="mb-6 flex gap-5 border-b border-line">
        @foreach (['pending' => __('Waiting'), 'approved' => __('Applied'), 'rejected' => __('Rejected')] as $value => $label)
            <x-ui.tab-link href="#" wire:click.prevent="$set('filters.status', '{{ $value }}')" :active="$filters['status'] === $value">{{ $label }}</x-ui.tab-link>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($rows->items as $request)
            <x-ui.card wire:key="req-{{ $request['id'] }}">
                <div class="flex flex-wrap items-start gap-4">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('students.show', $request['student']['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $request['student']['name'] }}</a>
                        <span class="font-mono text-xs text-ink-muted">{{ $request['student']['register_no'] }}</span>
                        <dl class="mt-2 space-y-1 text-sm">
                            @foreach ($request['changes'] as $field => $value)
                                <div class="flex gap-2"><dt class="text-ink-muted">{{ \App\Support\Format::label($field) }}:</dt><dd class="font-medium text-ink">{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                        <p class="mt-2 text-sm text-ink-muted">{{ $request['reason'] }}</p>
                        @if ($request['remarks'])<p class="mt-1 text-xs text-danger">{{ $request['remarks'] }}</p>@endif
                        <p class="mt-1 text-xs text-ink-subtle"><x-ui.time :value="$request['created_at']" />@if ($request['reviewed_by']) · {{ __('reviewed by :name', ['name' => $request['reviewed_by']]) }}@endif</p>
                    </div>
                    @if ($request['status'] === 'pending')
                        <x-ui.button size="sm" icon="check" wire:click="approve('{{ $request['id'] }}')" loading="approve">{{ __('Apply') }}</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" wire:click="askReject('{{ $request['id'] }}')">{{ __('Reject') }}</x-ui.button>
                    @else
                        <x-ui.status-badge :status="$request['status']" />
                    @endif
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="inbox" :title="__('Nothing here')" />
        @endforelse
    </div>
    <div class="mt-4"><x-ui.pagination :page="$rows" /></div>

    <x-ui.modal name="reject-change" :title="__('Reject request')" size="sm">
        <form wire:submit="reject" id="reject-change-form">
            <x-ui.input :label="__('Reason')" wire:model="remarks" required :hint="__('The student sees this.')" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'reject-change')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="reject-change-form" variant="danger" loading="reject">{{ __('Reject') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
