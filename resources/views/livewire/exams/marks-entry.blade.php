<div>
    <x-ui.page-header :title="$exam['course']['code'].' · '.$exam['course']['title']" :description="\App\Support\Format::date($exam['exam_date'], 'D, d M Y').' · '.__('out of :m', ['m' => $exam['max_marks'] + 0]).' · '.__(':e of :n scripts entered', ['e' => $entered, 'n' => count($exam['scripts'])])" />

    @unless ($exam['editable'])
        <x-ui.alert class="mb-4" tone="info">{{ __('Results are verified; marks can no longer change here. Use revaluation.') }}</x-ui.alert>
    @endunless

    <form wire:submit="save">
        <x-ui.table :empty="$exam['scripts'] === []">
            <x-slot:head>
                <x-ui.th>{{ __('Script code') }}</x-ui.th>
                <x-ui.th>{{ __('Attendance') }}</x-ui.th>
                <x-ui.th>{{ __('Marks') }}</x-ui.th>
            </x-slot:head>
            @foreach ($exam['scripts'] as $script)
                <x-ui.tr wire:key="sc-{{ $script['code'] }}">
                    <x-ui.td primary><span class="font-mono text-ink">{{ $script['code'] }}</span></x-ui.td>
                    <x-ui.td :label="__('Attendance')"><div class="w-32"><x-ui.select wire:model.live="entries.{{ $script['code'] }}.attendance" :options="['present' => __('Present'), 'absent' => __('Absent')]" :disabled="! $exam['editable']" /></div></x-ui.td>
                    <x-ui.td :label="__('Marks')"><div class="w-28"><x-ui.input type="number" step="0.01" min="0" :max="$exam['max_marks']" wire:model="entries.{{ $script['code'] }}.marks" :disabled="! $exam['editable'] || ($entries[$script['code']]['attendance'] ?? 'present') === 'absent'" /></div></x-ui.td>
                </x-ui.tr>
            @endforeach
            <x-slot:emptyState><x-ui.empty-state compact icon="file-text" :title="__('No eligible scripts')" :description="__('Scripts get codes when registration closes.')" /></x-slot:emptyState>
        </x-ui.table>
        @if ($exam['editable'] && $exam['scripts'] !== [])
            <div class="mt-4 flex justify-end"><x-ui.button type="submit" icon="save" loading="save">{{ __('Save marks') }}</x-ui.button></div>
        @endif
    </form>
</div>
