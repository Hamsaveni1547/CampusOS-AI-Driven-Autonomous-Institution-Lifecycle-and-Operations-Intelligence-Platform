@php($registration = $data['registration'])
<div>
    <x-ui.page-header :title="__('Term registration')" :description="$data['term']['name'] ?? __('No current term')">
        @if ($registration)
            <x-slot:meta><x-ui.status-badge :status="$registration['status']" /></x-slot:meta>
        @endif
    </x-ui.page-header>

    @if ($registration && $registration['status'] === 'rejected')
        <x-ui.alert class="mb-6" tone="danger" :title="__('Your registration was not approved')">{{ $registration['remarks'] }} {{ __('Change your choices and submit again.') }}</x-ui.alert>
    @elseif ($registration && $registration['status'] === 'submitted')
        <x-ui.alert class="mb-6" tone="info">{{ __('Submitted :date. Waiting for approval.', ['date' => \App\Support\Format::dateTime($registration['submitted_at'])]) }}</x-ui.alert>
    @elseif ($registration && $registration['status'] === 'approved')
        <x-ui.alert class="mb-6" tone="success">{{ __('Approved by :name. You are registered for :credits credits.', ['name' => $registration['decided_by'], 'credits' => $registration['total_credits']]) }}</x-ui.alert>
    @endif

    @if (! $data['term'])
        <x-ui.empty-state icon="calendar-check" :title="__('Registration is not open')" />
    @elseif (! $data['can_register'] && $registration)
        <x-ui.card :title="__('My courses')" :padding="false">
            @foreach ($registration['courses'] as $course)
                <div wire:key="rc-{{ $course['offering_id'] }}" class="flex items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-ink">{{ $course['title'] }}</p>
                        <p class="font-mono text-xs text-ink-muted">{{ $course['code'] }}</p>
                    </div>
                    @if ($course['type'] === 'elective')<x-ui.badge size="sm" tone="info">{{ __('Elective') }}</x-ui.badge>@endif
                    <span class="w-20 text-right text-sm">{{ __(':c credits', ['c' => $course['credits']]) }}</span>
                </div>
            @endforeach
        </x-ui.card>
    @elseif ($data['core'] === [] && $data['electives'] === [])
        <x-ui.empty-state icon="book-open" :title="__('No courses offered to your section yet')" :description="__('Check again once your department publishes this term’s courses.')" />
    @else
        <div class="space-y-6">
            <x-ui.card :title="__('Core courses')" :description="__('Everyone in your section takes these.')" :padding="false">
                @foreach ($data['core'] as $item)
                    <div wire:key="core-{{ $item['offering_id'] }}" class="flex items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <x-ui.icon name="lock" class="size-4 text-ink-subtle" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ $item['course']['title'] }}</p>
                            <p class="font-mono text-xs text-ink-muted">{{ $item['course']['code'] }}</p>
                        </div>
                        <span class="text-sm">{{ __(':c credits', ['c' => $item['course']['credits']]) }}</span>
                    </div>
                @endforeach
            </x-ui.card>

            @if ($data['electives'] !== [])
                <x-ui.card :title="__('Electives')" :description="__('Choose the electives you want to take.')" :padding="false">
                    @foreach ($data['electives'] as $item)
                        @php($full = $item['seats_left'] === 0 && ! in_array($item['offering_id'], $electives, true))
                        <label wire:key="el-{{ $item['offering_id'] }}" @class(['flex items-center gap-3 border-b border-line px-5 py-3 last:border-0', 'cursor-pointer hover:bg-surface-muted/50' => ! $full, 'opacity-60' => $full])>
                            <input type="checkbox" value="{{ $item['offering_id'] }}" wire:model.live="electives" @disabled($full) class="size-4 rounded border-line-strong text-primary focus:ring-primary">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-ink">{{ $item['course']['title'] }}</p>
                                <p class="text-xs text-ink-muted">
                                    <span class="font-mono">{{ $item['course']['code'] }}</span>
                                    @if ($item['seats_left'] !== null) · {{ $full ? __('Full') : trans_choice(':count seat left|:count seats left', $item['seats_left']) }}@endif
                                </p>
                            </div>
                            <span class="text-sm">{{ __(':c credits', ['c' => $item['course']['credits']]) }}</span>
                        </label>
                    @endforeach
                </x-ui.card>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line bg-surface px-5 py-4">
                <p class="text-sm text-ink">
                    {{ __('Total: :c credits', ['c' => $credits]) }}
                    @if ($data['credit_limit'] > 0)<span @class(['text-ink-muted', 'font-medium text-danger' => $credits > $data['credit_limit']])>/ {{ __('limit :n', ['n' => $data['credit_limit']]) }}</span>@endif
                </p>
                <x-ui.button x-on:click="$dispatch('open-dialog', 'confirm-registration')" :disabled="$data['credit_limit'] > 0 && $credits > $data['credit_limit']">{{ __('Submit registration') }}</x-ui.button>
            </div>
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </div>

        <x-ui.confirm-dialog name="confirm-registration" tone="primary" :title="__('Submit your registration?')" :confirm-label="__('Submit')" action="register">
            {{ __('You cannot change it unless it is sent back to you.') }}
        </x-ui.confirm-dialog>
    @endif
</div>
