<div>
    <x-ui.page-header :title="__('My marks')" :description="($data['term']['name'] ?? __('No current term')).' · '.__('Internal marks appear once they are verified.')" />

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($data['courses'] as $course)
            <x-ui.card wire:key="mm-{{ $course['class_id'] }}" :title="$course['course']['code'].' · '.$course['course']['title']" :padding="false">
                <x-slot:actions>
                    @if ($course['internal'])
                        <span class="text-lg font-semibold text-ink">{{ $course['internal']['final'] + 0 }}<span class="text-sm font-normal text-ink-muted"> / {{ $course['internal']['out_of'] + 0 }}</span></span>
                    @else
                        <x-ui.badge size="sm">{{ __('Internal pending') }}</x-ui.badge>
                    @endif
                </x-slot:actions>
                @forelse ($course['assessments'] as $item)
                    <div wire:key="mm-{{ $course['class_id'] }}-{{ $loop->index }}" class="flex items-center justify-between gap-3 border-b border-line px-5 py-2 text-sm last:border-0">
                        <span class="text-ink">{{ $item['name'] }}</span>
                        <span class="text-ink-muted">
                            @if ($item['status'] === 'present') <span class="font-medium text-ink">{{ $item['marks'] + 0 }}</span> / {{ $item['max_marks'] + 0 }}
                            @elseif ($item['status']) {{ $markStatuses[$item['status']] ?? $item['status'] }}
                            @else — / {{ $item['max_marks'] + 0 }}
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No assessments yet.') }}</p>
                @endforelse
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="clipboard-list" :title="__('No courses yet')" :description="__('Your courses appear after term registration.')" />
        @endforelse
    </div>
</div>
