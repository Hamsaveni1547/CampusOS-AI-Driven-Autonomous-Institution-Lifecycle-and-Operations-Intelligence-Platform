<div>
    <x-ui.page-header :title="$course['title']" :description="$course['code'].' · '.($course['department']['name'] ?? '')" :back="route('courses.index')" :back-label="__('Courses')">
        <x-slot:meta>
            <x-ui.badge>{{ $course['type_label'] }}</x-ui.badge>
            <x-ui.status-badge :status="$course['status']" size="sm" />
        </x-slot:meta>
        <x-slot:actions>
            @if ($canManage)<x-ui.button icon="pencil" wire:click="editSyllabus">{{ __('Edit syllabus') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-ui.stat :label="__('Credits')" :value="$course['credits']" />
        <x-ui.stat :label="__('Lecture')" :value="__(':n hrs/week', ['n' => $course['lecture_hours']])" />
        <x-ui.stat :label="__('Tutorial')" :value="__(':n hrs/week', ['n' => $course['tutorial_hours']])" />
        <x-ui.stat :label="__('Practical')" :value="__(':n hrs/week', ['n' => $course['practical_hours']])" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($course['units'] as $unit)
                <x-ui.card wire:key="u-{{ $unit['id'] }}" :title="__('Unit :n: :title', ['n' => $unit['number'], 'title' => $unit['title']])" :description="$unit['hours'] ? __(':n hours', ['n' => $unit['hours']]) : null">
                    <ul class="list-disc space-y-1 pl-5 text-sm text-ink">
                        @foreach ($unit['topics'] as $topic)<li wire:key="t-{{ $topic['id'] }}">{{ $topic['title'] }}</li>@endforeach
                    </ul>
                </x-ui.card>
            @empty
                <x-ui.empty-state icon="book-open" :title="__('No syllabus yet')" :description="$canManage ? __('Use “Edit syllabus” to add units and topics.') : null" />
            @endforelse
        </div>
        <div class="space-y-6">
            @if ($course['description'])
                <x-ui.card :title="__('About')"><p class="whitespace-pre-line text-sm text-ink-muted">{{ $course['description'] }}</p></x-ui.card>
            @endif
            <x-ui.card :title="__('Textbooks & references')">
                @forelse ($course['textbooks'] as $book)
                    <p class="border-b border-line py-2 text-sm text-ink last:border-0">{{ $book }}</p>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('None listed.') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>

    @if ($canManage)
        <x-ui.drawer name="syllabus-form" size="lg" :title="__('Edit syllabus')" :description="__('Write one topic per line. Saving replaces the whole syllabus.')">
            <form wire:submit="saveSyllabus" id="syllabus-form" class="space-y-5">
                @foreach ($units as $index => $unit)
                    <div wire:key="ue-{{ $index }}" class="space-y-3 rounded-lg border border-line p-4">
                        <div class="flex items-end gap-3">
                            <x-ui.input :label="__('Unit :n', ['n' => $index + 1])" wire:model="units.{{ $index }}.title" required class="flex-1" />
                            <x-ui.input :label="__('Hours')" type="number" min="0" wire:model="units.{{ $index }}.hours" class="w-24" />
                            <x-ui.button variant="ghost" icon="trash-2" wire:click="removeUnit({{ $index }})" :aria-label="__('Remove unit')" />
                        </div>
                        <x-ui.textarea :label="__('Topics')" wire:model="units.{{ $index }}.topics" rows="4" :hint="__('One per line')" />
                    </div>
                @endforeach
                <x-ui.button size="sm" variant="secondary" icon="plus" wire:click="addUnit">{{ __('Add unit') }}</x-ui.button>
                <x-ui.textarea :label="__('Description')" wire:model="description" rows="3" optional />
                <x-ui.textarea :label="__('Textbooks & references')" wire:model="textbooks" rows="3" optional :hint="__('One per line')" />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'syllabus-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="syllabus-form" loading="saveSyllabus">{{ __('Save syllabus') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.drawer>
    @endif
</div>
