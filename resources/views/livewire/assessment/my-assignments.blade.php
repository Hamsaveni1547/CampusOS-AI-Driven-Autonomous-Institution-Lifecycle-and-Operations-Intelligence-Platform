<div>
    <x-ui.page-header :title="__('My assignments')" :description="__('Assignments of your courses this term.')" />

    <x-ui.card :padding="false">
        @forelse ($assignments as $row)
            <div wire:key="ma-{{ $row['id'] }}" class="flex flex-wrap items-start gap-4 border-b border-line px-5 py-4 text-sm last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-ink">{{ $row['title'] }} <span class="text-xs font-normal text-ink-muted">{{ $row['course']['code'] }}</span></p>
                    <p class="text-xs text-ink-muted">{{ __('Due :d', ['d' => \App\Support\Format::dateTime($row['due_at'])]) }} · {{ __('out of :m', ['m' => $row['max_marks'] + 0]) }}@if ($row['allow_late']) · {{ __('late accepted') }}@endif</p>
                    @if ($row['instructions'])<p class="mt-1 whitespace-pre-line text-ink">{{ $row['instructions'] }}</p>@endif
                    @if ($row['file'])<a href="{{ route('files.download', $row['file']['id']) }}" class="text-xs text-primary hover:underline">{{ $row['file']['name'] }}</a>@endif
                    @if ($row['submission'])
                        <p class="mt-2 text-xs text-ink-muted">{{ __('Submitted :t', ['t' => \App\Support\Format::dateTime($row['submission']['submitted_at'])]) }}@if ($row['submission']['is_late']) · <span class="text-warning">{{ __('late') }}</span>@endif</p>
                        @if ($row['submission']['feedback'])<p class="text-xs text-ink">{{ __('Feedback: :f', ['f' => $row['submission']['feedback']]) }}</p>@endif
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if (($row['submission']['marks'] ?? null) !== null)
                        <span class="font-semibold text-ink">{{ $row['submission']['marks'] + 0 }} / {{ $row['max_marks'] + 0 }}</span>
                    @elseif ($row['submission'])
                        <x-ui.badge size="sm" tone="info">{{ __('Submitted') }}</x-ui.badge>
                    @elseif (! $row['can_submit'])
                        <x-ui.badge size="sm" tone="danger">{{ __('Missed') }}</x-ui.badge>
                    @endif
                    @if ($row['can_submit'])
                        <x-ui.button size="sm" :variant="$row['submission'] ? 'ghost' : 'primary'" icon="upload" wire:click="open('{{ $row['id'] }}', @js($row['title']), @js($row['submission']['text'] ?? null))">{{ $row['submission'] ? __('Resubmit') : __('Submit') }}</x-ui.button>
                    @endif
                </div>
            </div>
        @empty
            <x-ui.empty-state compact icon="file-text" :title="__('No assignments')" />
        @endforelse
    </x-ui.card>

    <x-ui.modal name="submit-assignment" :title="$title">
        <form wire:submit="submit" id="submit-assignment-form" class="space-y-4">
            <x-ui.textarea :label="__('Answer')" wire:model="text" rows="5" optional />
            <x-ui.file-upload :label="__('File')" wire:model="answer" :max-kb="20480" optional />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'submit-assignment')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="submit-assignment-form" loading="submit">{{ __('Submit') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
