<div>
    <x-ui.page-header :title="__('My results')" :description="__('Published end-term results.')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-ui.stat :label="__('CGPA')" :value="$data['cgpa'] ?? '—'" icon="award" />
        <x-ui.stat :label="__('Backlogs')" :value="$data['backlogs']" icon="triangle-alert" :tone="$data['backlogs'] > 0 ? 'danger' : 'success'" />
    </div>

    <div class="space-y-6">
        @forelse ($data['results'] as $result)
            <x-ui.card wire:key="rr-{{ $result['cycle']['id'] }}" :title="$result['cycle']['name']" :padding="false">
                <x-slot:actions><span class="text-sm text-ink-muted">{{ __('SGPA') }} <span class="font-semibold text-ink">{{ $result['sgpa'] ?? '—' }}</span> · {{ __('credits :e / :r', ['e' => $result['credits_earned'] + 0, 'r' => $result['credits_registered'] + 0]) }}</span></x-slot:actions>
                @foreach ($result['courses'] as $course)
                    <div wire:key="rc-{{ $course['id'] }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                        <span class="min-w-0 flex-1"><span class="font-medium text-ink">{{ $course['code'] }}</span> <span class="text-ink-muted">{{ $course['title'] }} · {{ __(':c credits', ['c' => $course['credits'] + 0]) }}</span></span>
                        <span class="text-xs text-ink-muted">{{ __('Internal :i · Exam :e', ['i' => $course['internal'] !== null ? $course['internal'] + 0 : '—', 'e' => $course['external'] !== null ? $course['external'] + 0 : '—']) }}</span>
                        <span @class(['w-10 text-right font-semibold', 'text-danger' => $course['passed'] === false, 'text-ink' => $course['passed'] !== false])>{{ $course['grade'] }}</span>
                        <span class="w-44 text-right">
                            @if ($course['revaluation'])
                                <span class="text-xs text-ink-muted">{{ __('Revaluation: :s', ['s' => \App\Support\Format::label($course['revaluation']['status'])]) }}@if ($course['revaluation']['new_marks'] !== null) ({{ $course['revaluation']['old_marks'] + 0 }} → {{ $course['revaluation']['new_marks'] + 0 }})@endif</span>
                            @elseif ($course['can_request_revaluation'])
                                <x-ui.button size="sm" variant="ghost" wire:click="openRevaluation('{{ $course['id'] }}', @js($course['code']))">{{ __('Ask for revaluation') }}</x-ui.button>
                            @endif
                        </span>
                    </div>
                @endforeach
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="award" :title="__('No results yet')" />
        @endforelse
    </div>

    <x-ui.modal name="revaluation-request" :title="__('Ask for revaluation')" size="sm">
        <form wire:submit="requestRevaluation" id="revaluation-request-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ $request['course'] }}</p>
            <x-ui.textarea :label="__('Reason')" wire:model="request.reason" rows="2" required />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'revaluation-request')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="revaluation-request-form" loading="requestRevaluation">{{ __('Send') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
