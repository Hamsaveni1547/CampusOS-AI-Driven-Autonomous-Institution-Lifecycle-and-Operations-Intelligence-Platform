<div>
    <x-ui.page-header :title="__('My feedback')" :description="__('What students said about your classes. Results are anonymous and shown only after enough responses.')" />

    <div class="space-y-6">
        @forelse ($surveys as $s)
            <div wire:key="tf-{{ $s['id'] }}">
                <h2 class="mb-3 text-sm font-semibold text-ink">{{ $s['title'] }} <span class="font-normal text-ink-muted">· {{ $s['term']['name'] ?? '' }}</span></h2>
                <div class="grid gap-4 lg:grid-cols-2">
                    @forelse ($s['classes'] as $class)
                        <x-ui.card wire:key="tfc-{{ $s['id'] }}-{{ $class['id'] }}" :title="$class['course']['code'].' · '.$class['course']['title']" :description="$class['section']">
                            <x-feedback.results :results="$class['results']" />
                        </x-ui.card>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('You did not teach a class in this term.') }}</p>
                    @endforelse
                </div>
            </div>
        @empty
            <x-ui.empty-state icon="message-square-heart" :title="__('No feedback yet')" />
        @endforelse
    </div>
</div>
