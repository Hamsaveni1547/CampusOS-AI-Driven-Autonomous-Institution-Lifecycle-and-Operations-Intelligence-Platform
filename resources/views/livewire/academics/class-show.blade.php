<div>
    <x-ui.page-header :title="$class['course']['title']" :description="$class['course']['code'].' · '.$class['section']['batch'].' '.$class['section']['name'].' · '.$class['term']['name']" :back="route('my-classes.index')" :back-label="__('My classes')">
        <x-slot:meta>
            <span class="text-sm text-ink-muted">{{ collect($class['faculty'])->pluck('name')->unique()->implode(', ') }}</span>
        </x-slot:meta>
        <x-slot:actions>
            @if ($class['can_teach'])<x-ui.button icon="upload" wire:click="openMaterial">{{ __('Share material') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6"><x-ui.progress :value="$class['coverage_percent']" show-value :label="__('Syllabus covered')" /></div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($class['units'] as $unit)
                <x-ui.card wire:key="cu-{{ $unit['id'] }}" :title="__('Unit :n: :title', ['n' => $unit['number'], 'title' => $unit['title']])" :padding="false">
                    @foreach ($unit['topics'] as $topic)
                        <div wire:key="ct-{{ $topic['id'] }}" class="flex items-start gap-3 border-b border-line px-5 py-2.5 last:border-0">
                            @if ($class['can_teach'])
                                <input type="checkbox" @checked($topic['covered_on']) wire:change="toggleTopic('{{ $topic['id'] }}', $event.target.checked)"
                                    class="mt-0.5 size-4 rounded border-line-strong text-primary focus:ring-primary" aria-label="{{ __('Taught') }}">
                            @else
                                <x-ui.icon :name="$topic['covered_on'] ? 'circle-check' : 'circle'" :class="$topic['covered_on'] ? 'mt-0.5 size-4 text-success' : 'mt-0.5 size-4 text-ink-subtle'" />
                            @endif
                            <div class="min-w-0 flex-1 text-sm">
                                <p @class(['text-ink', 'text-ink-muted' => $topic['covered_on']])>{{ $topic['title'] }}</p>
                                @if ($topic['covered_on'])
                                    <p class="text-xs text-ink-subtle">{{ __('Taught :date', ['date' => \App\Support\Format::date($topic['covered_on'])]) }}@if ($topic['covered_by']) · {{ $topic['covered_by'] }}@endif</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </x-ui.card>
            @empty
                <x-ui.empty-state icon="book-open" :title="__('No syllabus yet')" />
            @endforelse
        </div>

        <x-ui.card :title="__('Materials')" :padding="false">
            @forelse ($class['materials'] as $material)
                <div wire:key="cm-{{ $material['id'] }}" class="flex items-start gap-3 border-b border-line px-5 py-3 last:border-0">
                    <x-ui.icon :name="$material['url'] ? 'link' : 'file-text'" class="mt-0.5 size-4 text-ink-muted" />
                    <div class="min-w-0 flex-1 text-sm">
                        <a href="{{ $material['url'] ?? route('files.download', $material['file_id']) }}" @if ($material['url']) target="_blank" rel="noopener noreferrer" @endif class="font-medium text-ink hover:underline">{{ $material['title'] }}</a>
                        <p class="text-xs text-ink-subtle">
                            {{ $material['unit'] ? __('Unit :n', ['n' => $material['unit']['number']]) : __('General') }}
                            @unless ($material['is_published']) · <span class="text-warning">{{ __('hidden from students') }}</span>@endunless
                        </p>
                    </div>
                    @if ($class['can_teach'])
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="removeMaterial('{{ $material['id'] }}')" wire:confirm="{{ __('Remove this material?') }}" :aria-label="__('Remove')" />
                    @endif
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-ink-muted">{{ __('Nothing shared yet.') }}</p>
            @endforelse
        </x-ui.card>
    </div>

    @if ($class['can_teach'])
        <x-ui.modal name="material-form" :title="__('Share material')" :description="__('Shared with every section taking this course.')">
            <form wire:submit="addMaterial" id="material-form" class="space-y-4">
                <x-ui.input :label="__('Title')" wire:model="material.title" required />
                <x-ui.select :label="__('Unit')" wire:model="material.unit_id" :options="$units" :placeholder="__('General')" optional />
                <x-ui.file-upload :label="__('File')" wire:model="materialFile" :max-kb="20480" optional />
                <x-ui.input :label="__('Or a link')" type="url" wire:model="material.url" optional placeholder="https://" />
                <x-ui.switch wire:model="material.is_published" :label="__('Visible to students')" />
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'material-form')">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" form="material-form" loading="addMaterial">{{ __('Share') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
