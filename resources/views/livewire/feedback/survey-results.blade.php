<div>
    <x-ui.page-header :title="$data['title']" :description="\App\Support\Format::date($data['opens_on']).' – '.\App\Support\Format::date($data['closes_on'])" :back="route('surveys.index')" :back-label="__('Surveys')">
        <x-slot:meta><x-ui.status-badge :status="$data['status']" /></x-slot:meta>
    </x-ui.page-header>

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
