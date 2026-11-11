<x-layout.auth :title="__('Verify a document')">
    <div class="space-y-4">
        <div>
            <h1 class="text-lg font-semibold text-ink">{{ __('Verify a document') }}</h1>
            <p class="text-sm text-ink-muted">{{ __('Code :c', ['c' => $code]) }}</p>
        </div>

        @if ($document)
            <x-ui.alert tone="success" :title="__('This is a genuine CampusOS document')">
                {{ __('Issued by :i on :d.', ['i' => $document['institution'], 'd' => \App\Support\Format::date($document['issued_at'])]) }}
            </x-ui.alert>
            <dl class="divide-y divide-line rounded-lg border border-line text-sm">
                <div class="flex justify-between gap-4 px-4 py-2"><dt class="text-ink-muted">{{ __('Document') }}</dt><dd class="text-right font-medium text-ink">{{ $document['title'] }}</dd></div>
                <div class="flex justify-between gap-4 px-4 py-2"><dt class="text-ink-muted">{{ __('Issued to') }}</dt><dd class="text-right font-medium text-ink">{{ $document['issued_to'] }}</dd></div>
                @foreach ($document['summary'] as $label => $value)
                    <div wire:key="vs-{{ $loop->index }}" class="flex justify-between gap-4 px-4 py-2"><dt class="text-ink-muted">{{ $label }}</dt><dd class="text-right text-ink">{{ $value }}</dd></div>
                @endforeach
            </dl>
            <p class="text-xs text-ink-subtle">{{ __('Compare these details with the printed copy. If anything differs, the printout has been changed.') }}</p>
        @elseif ($error === 'not_found')
            <x-ui.alert tone="danger" :title="__('No document has this code')">{{ __('Check the code printed at the bottom of the document. It is not genuine if the code does not exist.') }}</x-ui.alert>
        @else
            <x-ui.alert tone="warning" :title="__('Cannot check right now')">{{ __('Please try again in a moment.') }}</x-ui.alert>
        @endif
    </div>
</x-layout.auth>
