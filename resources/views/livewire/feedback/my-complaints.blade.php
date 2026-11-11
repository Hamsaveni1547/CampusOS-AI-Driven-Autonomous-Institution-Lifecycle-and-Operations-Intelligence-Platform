<div>
    <x-ui.page-header :title="__('My complaints')" :description="__('Raise a grievance; you can choose to stay anonymous to staff.')">
        <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('Raise a complaint') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :padding="false">
        @forelse ($complaints as $c)
            <a wire:key="mc-{{ $c['id'] }}" href="{{ route('complaints.show', $c['id']) }}" wire:navigate class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0 hover:bg-surface-muted">
                <span class="min-w-0 flex-1"><span class="font-medium text-ink">{{ $c['subject'] }}</span><span class="block text-xs text-ink-muted">{{ $c['category']['name'] }} · {{ \App\Support\Format::date($c['raised_at']) }}@if ($c['is_anonymous']) · {{ __('anonymous') }}@endif</span></span>
                <x-ui.status-badge :status="$c['status']" size="sm" />
            </a>
        @empty
            <x-ui.empty-state compact icon="megaphone" :title="__('No complaints')" />
        @endforelse
    </x-ui.card>

    <x-ui.modal name="raise-complaint" :title="__('Raise a complaint')">
        <form wire:submit="raise" id="raise-complaint-form" class="space-y-4">
            <x-ui.input :label="__('Subject')" wire:model="form.subject" required />
            <x-ui.textarea :label="__('Details')" wire:model="form.description" rows="4" required />
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-0 flex-1"><x-ui.select :label="__('Category')" wire:model="form.category_id" :options="$categories" :placeholder="__('Choose…')" required /></div>
                @if (app(\App\Support\AiFeatures::class)->on('complaints'))
                    <x-ai.button size="md" class="mb-0.5" wire:click="suggestCategory" loading="suggestCategory">{{ __('Suggest category') }}</x-ai.button>
                @endif
            </div>
            @if ($suggestion)
                <p class="-mt-2 text-xs text-violet-700 dark:text-violet-300">
                    <x-ui.icon name="sparkles" class="inline size-3" /> {{ __('Suggested: :c · :u urgency. :r', ['c' => $suggestion['category'] ?? '—', 'u' => \App\Support\Format::label($suggestion['urgency']), 'r' => $suggestion['reason']]) }}
                </p>
            @endif
            <x-file-picker model="files" :files="$files" :label="__('Photos or documents')" />
            <x-ui.switch wire:model="form.anonymous" :label="__('Hide my name from staff')" />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'raise-complaint')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="raise-complaint-form" loading="raise">{{ __('Submit') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
