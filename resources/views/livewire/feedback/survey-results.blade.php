<div>
    <x-ui.page-header :title="$data['title']" :description="\App\Support\Format::date($data['opens_on']).' – '.\App\Support\Format::date($data['closes_on'])" :back="route('surveys.index')" :back-label="__('Surveys')">
        <x-slot:meta><x-ui.status-badge :status="$data['status']" /></x-slot:meta>
    </x-ui.page-header>

    @if (app(\App\Support\AiFeatures::class)->on('feedback'))
        <div class="mb-6 rounded-lg border border-violet-200 bg-violet-50/40 p-4 dark:border-violet-800 dark:bg-violet-950/20">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="flex items-center gap-1.5 text-sm font-semibold text-violet-800 dark:text-violet-300"><x-ui.icon name="sparkles" class="size-4" />{{ __('AI insights from comments') }}</span>
                <x-ai.button size="md" wire:click="analyse" loading="analyse">{{ $insights ? __('Analyse again') : __('Analyse comments') }}</x-ai.button>
            </div>
            @if ($formError)<x-ui.alert tone="warning" class="mt-3">{{ $formError }}</x-ui.alert>@endif
            @if ($insights)<x-ai.feedback-insights :insights="$insights" class="mt-4" />@endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card :title="__('Overall')"><x-feedback.results :results="$data['overall']" /></x-ui.card>
        <div class="space-y-4 lg:col-span-2">
            @foreach ($data['classes'] as $class)
                <x-ui.card wire:key="rc-{{ $class['id'] }}" :title="$class['course']['code'].' · '.$class['course']['title']" :description="$class['section'].' · '.implode(', ', $class['teachers'])">
                    <x-feedback.results :results="$class['results']" />
                </x-ui.card>
            @endforeach
        </div>
    </div>
</div>
