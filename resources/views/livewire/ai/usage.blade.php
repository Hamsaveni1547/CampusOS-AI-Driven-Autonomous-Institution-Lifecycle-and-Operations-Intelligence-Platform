@php
    $labels = ['writing' => __('Announcement writer'), 'complaints' => __('Complaint triage'), 'feedback' => __('Feedback insights'), 'assistant' => __('Chat assistant'), 'documents' => __('Document reading')];
    $max = max(1, collect($u['by_day'])->max('calls'));
@endphp
<div>
    <x-ui.page-header :title="__('AI usage')" :description="__('AI calls made by people in this college. Prompts and answers are not stored.')">
        <x-slot:actions><div class="w-36"><x-ui.select wire:model.live="days" :options="[7 => __('Last 7 days'), 14 => __('Last 14 days'), 30 => __('Last 30 days')]" /></div></x-slot:actions>
    </x-ui.page-header>

    @if ($u['provider'] === 'none')
        <x-ui.alert tone="warning" class="mb-6" :title="__('AI is not set up')">{{ __('Add GROQ_API_KEY (and optionally GEMINI_API_KEY) to the backend .env file to switch AI features on.') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-ui.stat :label="__('AI calls')" :value="$u['totals']['calls']" icon="sparkles" />
        <x-ui.stat :label="__('Failed')" :value="$u['totals']['failed']" icon="circle-alert" :tone="$u['totals']['failed'] > 0 ? 'danger' : 'success'" />
        <x-ui.stat :label="__('Tokens')" :value="number_format($u['totals']['tokens'])" icon="hash" />
        <x-ui.stat :label="__('Average time')" :value="$u['totals']['average_ms'] ? round($u['totals']['average_ms'] / 1000, 1).' s' : '—'" icon="timer" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2" :title="__('Calls per day')">
            <div class="flex h-44 items-end gap-1" role="img" aria-label="{{ __('Calls per day') }}">
                @foreach ($u['by_day'] as $d)
                    <div wire:key="d-{{ $d['date'] }}" class="group relative flex flex-1 flex-col justify-end" title="{{ \App\Support\Format::date($d['date']) }}: {{ $d['calls'] }} ({{ $d['failed'] }} failed)">
                        <div class="rounded-t bg-violet-500" style="height: {{ round(($d['calls'] - $d['failed']) * 100 / $max) }}%"></div>
                        @if ($d['failed'])<div class="bg-rose-400" style="height: {{ round($d['failed'] * 100 / $max) }}%"></div>@endif
                    </div>
                @endforeach
            </div>
            <div class="mt-1 flex justify-between text-[10px] text-ink-subtle">
                <span>{{ \App\Support\Format::date($u['by_day'][0]['date'] ?? now()->toDateString(), 'd M') }}</span>
                <span>{{ __('today') }}</span>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('By feature')" :padding="false">
            @forelse ($u['by_feature'] as $f)
                <div wire:key="f-{{ $f['feature'] }}" class="flex items-center justify-between border-b border-line px-5 py-2.5 text-sm last:border-0">
                    <span class="text-ink">{{ $labels[$f['feature']] ?? $f['feature'] }}</span>
                    <span class="text-ink-muted">{{ $f['calls'] }}@if ($f['failed']) · <span class="text-danger">{{ __(':n failed', ['n' => $f['failed']]) }}</span>@endif</span>
                </div>
            @empty
                <p class="px-5 py-3 text-sm text-ink-muted">{{ __('No AI calls yet.') }}</p>
            @endforelse
            <div class="border-t border-line px-5 py-2.5 text-xs text-ink-muted">
                {{ __('Provider: :p, fallback :f', ['p' => $u['provider'], 'f' => $u['fallback']]) }}
                @foreach ($u['by_provider'] as $p) · {{ $p['provider'] }} {{ $p['calls'] }}@endforeach
            </div>
        </x-ui.card>
    </div>

    {{-- Models --}}
    <x-ui.card class="mt-6" :title="__('Models')" :description="__('Providers retire models from time to time. Pick from what each provider offers your key today. “Quick” is used for short tasks; “Smart” for writing and the assistant.')">
        <x-slot:actions>
            <x-ui.button size="sm" variant="ghost" icon="refresh-cw" wire:click="refreshModels" loading="refreshModels">{{ __('Refresh lists') }}</x-ui.button>
            @canapi('admin.settings.manage')<x-ui.button size="sm" icon="save" wire:click="saveModels" loading="saveModels">{{ __('Save models') }}</x-ui.button>@endcanapi
        </x-slot:actions>
        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($m['providers'] ?? [] as $p)
                @php($name = $p['provider'])
                <div wire:key="prov-{{ $name }}" class="rounded-lg border border-line p-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <p class="font-medium text-ink">{{ ucfirst($name) }}
                            @if ($name === $m['provider'])<x-ui.badge size="sm" tone="info" class="ml-1">{{ __('Main') }}</x-ui.badge>@elseif ($name === $m['fallback'])<x-ui.badge size="sm" class="ml-1">{{ __('Backup') }}</x-ui.badge>@endif
                        </p>
                        @if ($p['configured'])
                            <x-ui.button size="sm" variant="secondary" icon="plug" wire:click="test('{{ $name }}')" loading="test">{{ __('Test') }}</x-ui.button>
                        @else
                            <x-ui.badge size="sm" tone="warning">{{ __('No key') }}</x-ui.badge>
                        @endif
                    </div>

                    @if (! $p['ok'])
                        <x-ui.alert tone="warning" class="mb-3">{{ __('Could not load models: :e', ['e' => $p['error']]) }}</x-ui.alert>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['fast' => __('Quick'), 'smart' => __('Smart')] as $tier => $label)
                            <div wire:key="sel-{{ $name }}-{{ $tier }}">
                                <x-ui.select :label="$label" wire:model="chosen.{{ $name }}.{{ $tier }}" :options="['' => __('Default: :m', ['m' => $p['defaults'][$tier]])] + collect($p['models'])->mapWithKeys(fn ($id) => [$id => $id])->all()" :disabled="! $p['configured']" />
                            </div>
                        @endforeach
                    </div>

                    @if ($t = $tests[$name] ?? null)
                        @if ($t['ok'])
                            <p class="mt-3 flex items-center gap-1.5 text-sm text-success"><x-ui.icon name="circle-check" class="size-4" />{{ __('Working: :m answered “:r” in :s s', ['m' => $t['model'], 'r' => $t['reply'], 's' => round($t['ms'] / 1000, 1)]) }}</p>
                        @else
                            <p class="mt-3 text-sm text-danger">{{ $t['error'] }}</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </x-ui.card>
</div>
