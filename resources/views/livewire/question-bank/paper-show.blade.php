<div>
    <x-ui.page-header :title="$paper['course']['code'].' · '.__('Set :s', ['s' => $paper['set_code']])" :description="$paper['blueprint']['name'].' · '.__(':m marks · :d min', ['m' => $paper['blueprint']['total_marks'] + 0, 'd' => $paper['blueprint']['duration_minutes']])" :back="route('question-papers.index')" :back-label="__('Question papers')">
        <x-slot:meta><x-ui.status-badge :status="$paper['status']" /></x-slot:meta>
        <x-slot:actions>
            @if ($paper['can']['view'])
                <x-ui.button variant="secondary" :icon="$showContent ? 'eye-off' : 'eye'" wire:click="$toggle('showContent')">{{ $showContent ? __('Hide paper') : __('Open paper') }}</x-ui.button>
            @endif
            @if ($paper['can']['submit'])<x-ui.button icon="send" wire:click="step('submit')">{{ __('Submit for review') }}</x-ui.button>@endif
            @if ($paper['can']['review'])
                <x-ui.button icon="check" wire:click="step('review', true)">{{ __('Approve') }}</x-ui.button>
                <x-ui.button variant="ghost" x-on:click="$dispatch('open-dialog', 'return-paper')">{{ __('Send back') }}</x-ui.button>
            @endif
            @if ($paper['can']['seal'])<x-ui.button icon="lock" wire:click="step('seal')" wire:confirm="{{ __('Seal this paper? It will be encrypted and cannot change.') }}">{{ __('Seal') }}</x-ui.button>@endif
            @if ($paper['can']['delete'])<x-ui.button variant="ghost" icon="trash-2" wire:click="delete" wire:confirm="{{ __('Delete this draft paper?') }}">{{ __('Delete') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($paper['remarks'] && $paper['status'] === 'draft')
        <x-ui.alert class="mb-4" tone="warning">{{ __('Sent back: :r', ['r' => $paper['remarks']]) }}</x-ui.alert>
    @endif

    <x-ui.card class="mb-6">
        <x-ui.description-list>
            <x-ui.description-item :label="__('Exam')">{{ $paper['exam'] ? $paper['exam']['cycle'].' · '.\App\Support\Format::date($paper['exam']['date']).' '.$paper['exam']['starts_at'] : __('Not linked') }}</x-ui.description-item>
            <x-ui.description-item :label="__('Can be opened')">{{ $paper['release_window'] ? \App\Support\Format::dateTime($paper['release_window']['opens']).' – '.\App\Support\Format::dateTime($paper['release_window']['closes']) : '—' }}</x-ui.description-item>
            <x-ui.description-item :label="__('Set by')">{{ $paper['set_by'] ?? '—' }}</x-ui.description-item>
            <x-ui.description-item :label="__('Approved by')">{{ $paper['approved_by'] ?? '—' }}</x-ui.description-item>
            @if ($paper['sealed_at'])<x-ui.description-item :label="__('Sealed')">{{ \App\Support\Format::dateTime($paper['sealed_at']) }}</x-ui.description-item>@endif
        </x-ui.description-list>
    </x-ui.card>

    @if ($content)
        <x-ui.alert class="mb-4" tone="info">{{ __('Opening this paper has been logged.') }}</x-ui.alert>
        <div class="mb-6 space-y-6">
            @foreach ($content['sections'] as $si => $section)
                <x-ui.card wire:key="ps-{{ $si }}" :title="$section['name'].' · '.($section['attempt'] ? __('answer any :a of :c', ['a' => $section['attempt'], 'c' => $section['count']]) : __(':c questions', ['c' => $section['count']])).' × '.($section['marks'] + 0)">
                    <ol class="list-decimal space-y-3 pl-5 text-sm">
                        @foreach ($section['questions'] as $qi => $q)
                            <li wire:key="pq-{{ $si }}-{{ $qi }}">
                                <span class="whitespace-pre-line text-ink">{{ $q['text'] }}</span> <span class="text-xs text-ink-muted">({{ $q['marks'] + 0 }}@if ($q['co_code']) · {{ $q['co_code'] }}@endif)</span>
                                @if ($q['options'])<ol class="mt-1 list-[lower-alpha] pl-5 text-ink-muted">@foreach ($q['options'] as $o)<li>{{ $o }}</li>@endforeach</ol>@endif
                                @if ($q['answer'])<p class="mt-1 text-xs text-success">{{ __('Answer: :a', ['a' => $q['answer']]) }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    @if ($paper['can']['logs'])
        <x-ui.card :title="__('Access log')" :padding="false">
            @forelse ($logs as $log)
                <div wire:key="lg-{{ $loop->index }}" class="flex flex-wrap gap-3 border-b border-line px-5 py-2 text-sm last:border-0">
                    <span class="w-40 text-ink-muted">{{ \App\Support\Format::dateTime($log['at']) }}</span>
                    <span class="font-medium text-ink">{{ \App\Support\Format::label($log['action']) }}</span>
                    <span class="flex-1 text-ink-muted">{{ $log['user'] ?? '—' }}@if ($log['ip']) · {{ $log['ip'] }}@endif</span>
                </div>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('Nobody has opened this paper yet.') }}</p>
            @endforelse
        </x-ui.card>
    @endif

    <x-ui.modal name="return-paper" :title="__('Send back to the setter')" size="sm">
        <form wire:submit="step('review', false)" id="return-paper-form"><x-ui.textarea :label="__('What needs to change')" wire:model="remarks" rows="3" required /></form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'return-paper')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="return-paper-form" variant="danger" loading="step">{{ __('Send back') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
