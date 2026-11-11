<div class="flex h-[calc(100vh-9rem)] min-h-[32rem] gap-6">
    {{-- Conversations --}}
    <aside class="hidden w-64 shrink-0 flex-col rounded-lg border border-line bg-surface lg:flex">
        <div class="border-b border-line p-3">
            <x-ui.button class="w-full" icon="plus" wire:click="newChat">{{ __('New chat') }}</x-ui.button>
        </div>
        <div class="flex-1 overflow-y-auto">
            @forelse ($conversations as $c)
                <div wire:key="cv-{{ $c['id'] }}" @class(['group flex items-center gap-2 border-b border-line px-3 py-2 text-sm last:border-0', 'bg-primary-soft/40' => $c['id'] === $conversationId])>
                    <button type="button" wire:click="open('{{ $c['id'] }}')" class="min-w-0 flex-1 text-left">
                        <span class="block truncate text-ink">{{ $c['title'] }}</span>
                        <span class="block text-[11px] text-ink-subtle">{{ \App\Support\Format::dateTime($c['updated_at']) }}</span>
                    </button>
                    <button type="button" wire:click="delete('{{ $c['id'] }}')" wire:confirm="{{ __('Delete this conversation?') }}" class="hidden text-ink-subtle hover:text-danger group-hover:block" aria-label="{{ __('Delete') }}"><x-ui.icon name="trash-2" class="size-4" /></button>
                </div>
            @empty
                <p class="p-3 text-sm text-ink-muted">{{ __('Your chats appear here.') }}</p>
            @endforelse
        </div>
    </aside>

    {{-- Chat --}}
    <section class="flex min-w-0 flex-1 flex-col rounded-lg border border-line bg-surface">
        <header class="flex items-center gap-3 border-b border-line px-5 py-3">
            <span class="flex size-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 text-white"><x-ui.icon name="sparkles" class="size-5" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-base font-semibold text-ink">{{ __('Ask CampusOS') }}</h1>
                <p class="text-xs text-ink-muted">{{ __('Answers from your own records. English or ಕನ್ನಡ. It can only read, never change anything.') }}</p>
            </div>
            <x-ui.button class="lg:hidden" size="sm" variant="ghost" icon="plus" wire:click="newChat">{{ __('New') }}</x-ui.button>
        </header>

        <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4" x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$wire.messages; $nextTick(() => $el.scrollTop = $el.scrollHeight)">
            @if ($messages === [])
                <div class="mx-auto max-w-xl py-8 text-center">
                    <x-ui.icon name="bot" class="mx-auto mb-3 size-10 text-violet-500" />
                    <p class="text-base font-medium text-ink">{{ __('Hi :n, what would you like to know?', ['n' => strtok((string) $currentUser?->name(), ' ')]) }}</p>
                    <div class="mt-5 grid gap-2 sm:grid-cols-2">
                        @foreach ($suggestions as $s)
                            <button type="button" wire:key="sg-{{ $loop->index }}" wire:click="ask({{ \Illuminate\Support\Js::from($s) }})" class="rounded-lg border border-line px-3 py-2 text-left text-sm text-ink hover:border-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/30">{{ $s }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($messages as $m)
                <div wire:key="m-{{ $loop->index }}" @class(['flex', 'justify-end' => $m['role'] === 'user'])>
                    <div @class([
                        'max-w-[85%] rounded-2xl px-4 py-2.5 text-sm',
                        'rounded-br-sm bg-primary text-white' => $m['role'] === 'user',
                        'rounded-bl-sm border border-line bg-surface-muted text-ink' => $m['role'] !== 'user',
                    ])>
                        <div class="whitespace-pre-line leading-relaxed">{{ $m['content'] }}</div>
                        @if (($m['tools'] ?? []) !== [])
                            <p class="mt-1.5 flex flex-wrap gap-1 text-[11px] text-ink-subtle">
                                <x-ui.icon name="search" class="size-3" />{{ __('Looked up:') }}
                                @foreach ($m['tools'] as $t)<span wire:key="mt-{{ $loop->parent->index }}-{{ $t }}" class="rounded bg-violet-100 px-1.5 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">{{ $toolLabels[$t] ?? $t }}</span>@endforeach
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach

            <div wire:loading.flex wire:target="ask" class="items-center gap-2 text-sm text-ink-muted">
                <x-ui.spinner class="size-4" /> {{ __('Looking it up…') }}
            </div>
        </div>

        @if ($formError)<x-ui.alert tone="warning" class="mx-5 mb-2">{{ $formError }}</x-ui.alert>@endif

        <form wire:submit="ask" class="flex items-end gap-2 border-t border-line p-3">
            <textarea wire:model="question" rows="1" maxlength="1000" placeholder="{{ __('Ask about your attendance, timetable, fees, results…') }}"
                x-on:keydown.enter.prevent="if (! $event.shiftKey) $wire.ask()"
                class="max-h-32 min-h-[2.5rem] flex-1 resize-none rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none"></textarea>
            <x-ui.button type="submit" icon="send-horizontal" loading="ask">{{ __('Ask') }}</x-ui.button>
        </form>
    </section>
</div>
