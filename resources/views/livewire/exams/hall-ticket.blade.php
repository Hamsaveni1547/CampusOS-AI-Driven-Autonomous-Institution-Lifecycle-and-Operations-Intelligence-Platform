<div>
    <div class="mb-4 flex justify-between print:hidden">
        <x-ui.button variant="secondary" icon="arrow-left" :href="route('my-exams.index')">{{ __('My exams') }}</x-ui.button>
        <x-ui.button icon="printer" x-on:click="window.print()">{{ __('Print') }}</x-ui.button>
    </div>

    <div class="mx-auto max-w-3xl rounded-lg border border-line bg-surface p-8 print:border-0 print:p-0">
        <div class="mb-6 border-b border-line pb-4 text-center">
            <p class="text-lg font-semibold text-ink">{{ __('Hall ticket') }}</p>
            <p class="text-sm text-ink-muted">{{ $ticket['cycle']['name'] }}</p>
        </div>

        <div class="mb-6 flex items-start gap-6">
            <div class="min-w-0 flex-1">
                <x-ui.description-list>
                    <x-ui.description-item :label="__('Name')">{{ $ticket['student']['name'] }}</x-ui.description-item>
                    <x-ui.description-item :label="__('Register number')"><span class="font-mono">{{ $ticket['student']['register_no'] }}</span></x-ui.description-item>
                    <x-ui.description-item :label="__('Programme')">{{ $ticket['student']['programme'] }}</x-ui.description-item>
                </x-ui.description-list>
            </div>
            @if ($ticket['student']['photo_id'])
                <img src="{{ route('files.download', $ticket['student']['photo_id']) }}" alt="{{ __('Photo') }}" class="h-32 w-28 rounded border border-line object-cover">
            @endif
        </div>

        <table class="w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase text-ink-muted">
                <tr><th class="py-2">{{ __('Date') }}</th><th class="py-2">{{ __('Time') }}</th><th class="py-2">{{ __('Course') }}</th><th class="py-2">{{ __('Invigilator') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($ticket['exams'] as $exam)
                    <tr wire:key="ht-{{ $exam['code'] }}" class="border-b border-line">
                        <td class="py-2">{{ $exam['exam'] ? \App\Support\Format::date($exam['exam']['date'], 'D, d M Y') : '—' }}</td>
                        <td class="py-2 font-mono text-xs">{{ $exam['exam'] ? $exam['exam']['starts_at'].'–'.$exam['exam']['ends_at'] : '—' }}</td>
                        <td class="py-2"><span class="font-medium">{{ $exam['code'] }}</span> {{ $exam['title'] }}</td>
                        <td class="py-2"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="mt-10 text-right text-xs text-ink-muted">{{ __('Controller of examinations') }}</p>
    </div>
</div>
