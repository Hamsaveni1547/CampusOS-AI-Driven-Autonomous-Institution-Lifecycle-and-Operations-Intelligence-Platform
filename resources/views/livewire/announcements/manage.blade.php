<div>
    <x-ui.page-header :title="__('Post announcements')" :description="__('Save a draft, check it, then publish. Publishing notifies everyone in the audience.')" :back="route('announcements.index')" :back-label="__('Announcements')">
        <x-slot:actions><x-ui.button icon="plus" wire:click="open">{{ __('New announcement') }}</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 w-48"><x-ui.select wire:model.live="status" :options="['draft' => __('Drafts'), 'published' => __('Published'), 'withdrawn' => __('Withdrawn')]" :placeholder="__('All')" /></div>

    <x-ui.table :empty="$items === []">
        <x-slot:head>
            <x-ui.th>{{ __('Announcement') }}</x-ui.th>
            <x-ui.th>{{ __('Audience') }}</x-ui.th>
            <x-ui.th>{{ __('Seen') }}</x-ui.th>
            <x-ui.th><span class="sr-only">{{ __('Actions') }}</span></x-ui.th>
        </x-slot:head>
        @foreach ($items as $a)
            <x-ui.tr wire:key="ma-{{ $a['id'] }}">
                <x-ui.td primary>
                    <a href="{{ route('announcements.show', $a['id']) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $a['title'] }}</a>
                    <span class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-ink-subtle"><x-ui.status-badge :status="$a['status']" size="sm" />{{ $a['from'] }}@if ($a['published_at']) · {{ \App\Support\Format::date($a['published_at']) }}@endif</span>
                </x-ui.td>
                <x-ui.td :label="__('Audience')">{{ $a['audience'] }}@if ($a['recipients_count'])<span class="block text-xs text-ink-muted">{{ trans_choice(':n person|:n people', $a['recipients_count'], ['n' => $a['recipients_count']]) }}</span>@endif</x-ui.td>
                <x-ui.td :label="__('Seen')">@if ($a['status'] !== 'draft'){{ $a['read_count'] }} / {{ $a['recipients_count'] }}@if ($a['requires_ack'])<span class="block text-xs text-ink-muted">{{ __(':n acknowledged', ['n' => $a['ack_count']]) }}</span>@endif @else — @endif</x-ui.td>
                <x-ui.td class="whitespace-nowrap text-right">
                    @if ($a['status'] === 'draft')
                        <x-ui.button size="sm" variant="ghost" wire:click="open({{ \Illuminate\Support\Js::from($a) }})">{{ __('Edit') }}</x-ui.button>
                        <x-ui.button size="sm" :icon="$a['publish_at'] ? 'clock' : 'send'" wire:click="publish('{{ $a['id'] }}')" wire:confirm="{{ $a['publish_at'] ? __('Schedule it to go out at the chosen time?') : __('Publish and notify everyone in the audience?') }}">{{ $a['publish_at'] ? __('Schedule') : __('Publish') }}</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" icon="trash-2" wire:click="delete('{{ $a['id'] }}')" wire:confirm="{{ __('Delete this draft?') }}"><span class="sr-only">{{ __('Delete') }}</span></x-ui.button>
                    @elseif ($a['status'] === 'scheduled')
                        <span class="mr-2 text-xs text-ink-muted">{{ __('Goes out :d', ['d' => \App\Support\Format::dateTime($a['publish_at'])]) }}</span>
                        <x-ui.button size="sm" variant="ghost" wire:click="withdraw('{{ $a['id'] }}')">{{ __('Cancel schedule') }}</x-ui.button>
                    @elseif ($a['status'] === 'published')
                        <x-ui.button size="sm" variant="ghost" wire:click="withdraw('{{ $a['id'] }}')" wire:confirm="{{ __('Withdraw this announcement? People will no longer see it.') }}">{{ __('Withdraw') }}</x-ui.button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        <x-slot:emptyState><x-ui.empty-state compact icon="megaphone" :title="__('No announcements yet')" /></x-slot:emptyState>
    </x-ui.table>

    <x-ui.modal name="announcement-form" :title="$editingId ? __('Edit draft') : __('New announcement')" size="lg">
        <form wire:submit="save" id="announcement-form-el" class="grid gap-4 sm:grid-cols-2">
            @if (app(\App\Support\AiFeatures::class)->on('writing'))
                <div class="rounded-lg border border-violet-200 bg-violet-50/60 p-3 sm:col-span-2 dark:border-violet-800 dark:bg-violet-950/30">
                    <p class="mb-2 flex items-center gap-1.5 text-sm font-medium text-violet-800 dark:text-violet-300"><x-ui.icon name="wand-sparkles" class="size-4" />{{ __('Write with AI') }}</p>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="min-w-0 flex-1"><x-ui.input wire:model="aiIdea" :placeholder="__('e.g. Lab exams postponed to next Monday because of the power cut')" /></div>
                        <div class="w-32"><x-ui.select wire:model="aiTone" :options="['friendly' => __('Friendly'), 'formal' => __('Formal'), 'urgent' => __('Urgent')]" /></div>
                        <x-ai.button size="md" wire:click="aiDraft" loading="aiDraft">{{ __('Write') }}</x-ai.button>
                    </div>
                    @error('aiIdea')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
            @endif
            <x-ui.input :label="__('Title')" wire:model="form.title" required class="sm:col-span-2" />
            <x-ui.textarea :label="__('Message')" wire:model="form.body" rows="6" required class="sm:col-span-2" />
            @if (app(\App\Support\AiFeatures::class)->on('writing'))
                <div class="-mt-2 flex flex-wrap items-center gap-2 sm:col-span-2">
                    <x-ai.button wire:click="aiRewrite('improve')" loading="aiRewrite">{{ __('Improve') }}</x-ai.button>
                    <x-ai.button wire:click="aiRewrite('shorten')" loading="aiRewrite">{{ __('Shorten') }}</x-ai.button>
                    <x-ai.button wire:click="aiRewrite('formal')" loading="aiRewrite">{{ __('Formal') }}</x-ai.button>
                    <x-ai.button wire:click="aiRewrite('kannada')" loading="aiRewrite">{{ __('ಕನ್ನಡ') }}</x-ai.button>
                    <x-ai.button wire:click="aiRewrite('english')" loading="aiRewrite">{{ __('English') }}</x-ai.button>
                    @if ($aiUndo !== null)<button type="button" wire:click="aiUndoChange" class="text-xs text-ink-muted underline">{{ __('Undo AI change') }}</button>@endif
                </div>
            @endif
            <x-ui.select :label="__('Category')" wire:model="form.category" :options="$categories" required />
            <x-ui.select :label="__('Priority')" wire:model="form.priority" :options="['normal' => __('Normal'), 'important' => __('Important')]" required />
            <x-ui.select :label="__('Send to')" wire:model.live="form.audience_type" :options="$types" :placeholder="__('Choose…')" required />
            <x-ui.input type="date" :label="__('Show until')" wire:model="form.expires_on" optional />
            <x-ui.input type="datetime-local" :label="__('Send later at')" wire:model="form.publish_at" optional :hint="__('Leave empty to send when you publish.')" />

            @if ($form['audience_type'] === 'sections')
                <div class="sm:col-span-2"><x-ui.select :label="__('Batch')" wire:model.live="sectionBatch" :options="$batches" :placeholder="__('Choose batch…')" /></div>
            @endif
            @if ($targets !== [])
                <div class="grid gap-2 sm:col-span-2 sm:grid-cols-3">
                    @foreach ($targets as $value => $label)
                        <x-ui.checkbox wire:key="tg-{{ $form['audience_type'] }}-{{ $value }}" wire:model="form.audience_ids" value="{{ $value }}" :label="$label" />
                    @endforeach
                </div>
            @endif
            @error('form.audience_ids')<p class="text-sm text-danger sm:col-span-2">{{ $message }}</p>@enderror

            <x-file-picker model="files" :files="$files" :label="__('Attach circular or photos')" class="sm:col-span-2" />
            <x-ui.switch wire:model="form.is_pinned" :label="__('Pin to the top')" />
            <x-ui.switch wire:model="form.requires_ack" :label="__('Ask people to acknowledge')" />
            @if ($formError)<x-ui.alert tone="danger" class="sm:col-span-2">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'announcement-form')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="announcement-form-el" loading="save">{{ __('Save draft') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
