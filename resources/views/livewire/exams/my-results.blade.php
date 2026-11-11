@php
    $bySemester = collect($data['results'])->sortBy(fn ($r) => sprintf('%02d-%s', ($r['semester'] ?? null) ?? 99, $r['cycle']['published_at'] ?? ''))->values();
@endphp
<div>
    <x-ui.page-header :title="__('My results')" :description="__('Grade cards for every semester, with SGPA and CGPA.')">
        <x-slot:actions>
            @if ($bySemester->isNotEmpty())
                <x-ui.button variant="secondary" icon="printer" x-on:click="window.print()">{{ __('Print') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat :label="__('CGPA')" :value="$data['cgpa'] ?? '—'" icon="award" />
        <x-ui.stat :label="__('Semesters with results')" :value="$bySemester->pluck('semester')->filter()->unique()->count()" icon="layers" />
        <x-ui.stat :label="__('Backlogs')" :value="$data['backlogs']" icon="triangle-alert" :tone="$data['backlogs'] > 0 ? 'danger' : 'success'" />
    </div>

    @if ($bySemester->isEmpty())
        <x-ui.empty-state icon="award" :title="__('No results yet')" />
    @else
        <div x-data="{ open: {{ \Illuminate\Support\Js::from($bySemester->last()['cycle']['id']) }} }" class="space-y-4">
            <div class="flex flex-wrap gap-2 print:hidden">
                @foreach ($bySemester as $result)
                    <button type="button" wire:key="tab-{{ $result['cycle']['id'] }}" x-on:click="open = {{ \Illuminate\Support\Js::from($result['cycle']['id']) }}"
                        :class="open === {{ \Illuminate\Support\Js::from($result['cycle']['id']) }} ? 'border-primary bg-primary-soft text-primary-ink' : 'border-line text-ink-muted hover:border-primary/50'"
                        class="rounded-full border px-3 py-1 text-sm">
                        {{ ($result['semester'] ?? null) ? __('Semester :n', ['n' => ($result['semester'] ?? null)]) : ($result['term'] ?? '') }}@if (($result['cycle']['type'] ?? 'regular') === 'supplementary') · {{ __('Supplementary') }}@endif
                    </button>
                @endforeach
            </div>

            @foreach ($bySemester as $result)
                <section wire:key="sem-{{ $result['cycle']['id'] }}" x-show="open === {{ \Illuminate\Support\Js::from($result['cycle']['id']) }}" class="print:!block">
                    <x-ui.card :padding="false">
                        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-line px-5 py-4">
                            <div>
                                <h2 class="text-base font-semibold text-ink">{{ ($result['semester'] ?? null) ? __('Semester :n', ['n' => ($result['semester'] ?? null)]) : ($result['term'] ?? '') }} · {{ $result['cycle']['name'] }}</h2>
                                <p class="text-xs text-ink-muted">{{ ($result['term'] ?? '') }}@if ($result['cycle']['published_at']) · {{ __('published :d', ['d' => \App\Support\Format::date($result['cycle']['published_at'])]) }}@endif</p>
                            </div>
                            <dl class="flex gap-6 text-sm">
                                <div><dt class="text-xs text-ink-muted">{{ __('SGPA') }}</dt><dd class="text-lg font-semibold text-ink">{{ $result['sgpa'] ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-ink-muted">{{ __('CGPA') }}</dt><dd class="text-lg font-semibold text-ink">{{ $result['cgpa'] ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-ink-muted">{{ __('Credits') }}</dt><dd class="text-lg font-semibold text-ink">{{ $result['credits_earned'] + 0 }} / {{ $result['credits_registered'] + 0 }}</dd></div>
                            </dl>
                            <x-ui.button size="sm" variant="secondary" icon="download" class="print:hidden" :href="route('documents.pdf', ['grade-card', $result['cycle']['id']])">{{ __('Grade card PDF') }}</x-ui.button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-ink-subtle">
                                    <tr>
                                        <th class="px-4 py-2">{{ __('Course') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('Credits') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('Internal') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('Exam') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('Total') }}</th>
                                        <th class="px-3 py-2 text-center">{{ __('Grade') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('Points') }}</th>
                                        <th class="px-3 py-2">{{ __('Result') }}</th>
                                        <th class="px-3 py-2 print:hidden"><span class="sr-only">{{ __('Revaluation') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody class="tabular-nums">
                                    @foreach ($result['courses'] as $course)
                                        <tr wire:key="rc-{{ $course['id'] }}" class="border-t border-line">
                                            <td class="px-4 py-2"><span class="font-medium text-ink">{{ $course['code'] }}</span> <span class="block text-xs text-ink-muted">{{ $course['title'] }}</span></td>
                                            <td class="px-3 py-2 text-right">{{ $course['credits'] + 0 }}</td>
                                            <td class="px-3 py-2 text-right">{{ $course['internal'] !== null ? $course['internal'] + 0 : '—' }}</td>
                                            <td class="px-3 py-2 text-right">{{ $course['external'] !== null ? $course['external'] + 0 : '—' }}</td>
                                            <td class="px-3 py-2 text-right font-medium">{{ ($course['total'] ?? null) !== null ? $course['total'] + 0 : '—' }}</td>
                                            <td @class(['px-3 py-2 text-center font-semibold', 'text-danger' => $course['passed'] === false, 'text-ink' => $course['passed'] !== false])>{{ $course['grade'] ?? '—' }}</td>
                                            <td class="px-3 py-2 text-right">{{ ($course['grade_points'] ?? null) !== null ? $course['grade_points'] + 0 : '—' }}</td>
                                            <td class="px-3 py-2">@if ($course['passed'] === false)<x-ui.badge size="sm" tone="danger">{{ __('Fail') }}</x-ui.badge>@elseif ($course['passed'])<x-ui.badge size="sm" tone="success">{{ __('Pass') }}</x-ui.badge>@endif</td>
                                            <td class="px-3 py-2 text-right print:hidden">
                                                @if ($course['revaluation'])
                                                    <span class="text-xs text-ink-muted">{{ __('Revaluation: :s', ['s' => \App\Support\Format::label($course['revaluation']['status'])]) }}@if ($course['revaluation']['new_marks'] !== null) ({{ $course['revaluation']['old_marks'] + 0 }} → {{ $course['revaluation']['new_marks'] + 0 }})@endif</span>
                                                @elseif ($course['can_request_revaluation'])
                                                    <x-ui.button size="sm" variant="ghost" wire:click="openRevaluation('{{ $course['id'] }}', {{ \Illuminate\Support\Js::from($course['code']) }})">{{ __('Revaluation') }}</x-ui.button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                </section>
            @endforeach
        </div>
    @endif

    <x-ui.modal name="revaluation-request" :title="__('Ask for revaluation')" size="sm">
        <form wire:submit="requestRevaluation" id="revaluation-request-form" class="space-y-4">
            <p class="text-sm text-ink-muted">{{ $request['course'] }}</p>
            <x-ui.textarea :label="__('Reason')" wire:model="request.reason" rows="2" required />
            @if ($formError)<x-ui.alert tone="danger">{{ $formError }}</x-ui.alert>@endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'revaluation-request')">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button type="submit" form="revaluation-request-form" loading="requestRevaluation">{{ __('Send') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
