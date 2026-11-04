<div>
    <x-ui.page-header :title="__('Give feedback')" :description="__('Your answers are anonymous: they are stored without your name.')" />

    @if ($active)
        <x-ui.card :title="$active['title']" :description="$active['class'] ? $active['class']['course']['code'].' · '.$active['class']['course']['title'].' · '.implode(', ', $active['class']['teachers']) : $active['description']">
            <form wire:submit="submit" class="space-y-5">
                @foreach ($active['questions'] as $q)
                    <div wire:key="aq-{{ $q['id'] }}">
                        <p class="mb-2 text-sm font-medium text-ink">{{ $loop->iteration }}. {{ $q['text'] }}@if ($q['required'])<span class="text-danger"> *</span>@endif</p>
                        @if ($q['type'] === 'rating')
                            <div class="flex gap-2">
                                @foreach (range(1, 5) as $n)
                                    <label wire:key="r-{{ $q['id'] }}-{{ $n }}" class="cursor-pointer">
                                        <input type="radio" class="peer sr-only" wire:model="answers.{{ $q['id'] }}" value="{{ $n }}">
                                        <span class="flex size-10 items-center justify-center rounded-md border border-line text-sm peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">{{ $n }}</span>
                                    </label>
                                @endforeach
                                <span class="ml-2 self-center text-xs text-ink-muted">{{ __('1 = poor, 5 = excellent') }}</span>
                            </div>
                        @elseif ($q['type'] === 'choice')
                            <x-ui.radio-group wire:model="answers.{{ $q['id'] }}" :options="array_combine($q['options'], $q['options'])" />
                        @else
                            <x-ui.textarea wire:model="answers.{{ $q['id'] }}" rows="2" />
                        @endif
                    </div>
                @endforeach
                @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
                <div class="flex gap-2">
                    <x-ui.button type="submit" icon="send" loading="submit">{{ __('Submit') }}</x-ui.button>
                    <x-ui.button variant="secondary" wire:click="$set('current', null)">{{ __('Back') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @else
        <x-ui.card :padding="false">
            @forelse ($pending as $i => $p)
                <div wire:key="ps-{{ $i }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-ink">{{ $p['title'] }}</p>
                        <p class="text-xs text-ink-muted">@if ($p['class']){{ $p['class']['course']['code'] }} · {{ $p['class']['course']['title'] }} · {{ implode(', ', $p['class']['teachers']) }} · @endif{{ __('until :d', ['d' => \App\Support\Format::date($p['closes_on'])]) }}</p>
                    </div>
                    <x-ui.button size="sm" wire:click="start({{ $i }})">{{ __('Answer') }}</x-ui.button>
                </div>
            @empty
                <x-ui.empty-state compact icon="circle-check" :title="__('Nothing to answer')" :description="__('Thank you — you are all caught up.')" />
            @endforelse
        </x-ui.card>
    @endif
</div>
