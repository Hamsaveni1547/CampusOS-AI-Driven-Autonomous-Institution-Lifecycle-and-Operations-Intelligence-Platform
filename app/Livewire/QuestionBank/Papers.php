<?php

namespace App\Livewire\QuestionBank;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Blueprints of a course, generating question paper sets, and the list of papers.
 */
#[Layout('layouts::app')]
#[Title('Question papers')]
class Papers extends Component
{
    use InteractsWithApi, WithTable;

    #[Url(except: '')]
    public string $course = '';

    /** @var array<string, mixed> */
    public array $blueprint = [];

    /** @var array<string, mixed> */
    public array $generation = ['blueprint_id' => '', 'sets' => 1, 'exam_schedule_id' => ''];

    protected function defaultFilters(): array
    {
        return ['status' => ''];
    }

    public function openBlueprint(?string $id = null): void
    {
        $existing = $id ? collect($this->blueprints())->firstWhere('id', $id) : null;
        $this->blueprint = [
            'id' => $id, 'name' => $existing['name'] ?? '', 'duration_minutes' => $existing['duration_minutes'] ?? 180, 'avoid_recent_papers' => $existing['avoid_recent_papers'] ?? 2,
            'sections' => collect($existing['sections'] ?? [['name' => 'Part A', 'type' => 'short', 'marks' => 2, 'count' => 10, 'attempt' => null]])
                ->map(fn (array $s) => ['name' => $s['name'], 'type' => $s['type'], 'marks' => $s['marks'], 'count' => $s['count'], 'attempt' => $s['attempt'] ?? ''])->all(),
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'blueprint-form');
    }

    public function addSection(): void
    {
        $this->blueprint['sections'][] = ['name' => '', 'type' => 'long', 'marks' => 10, 'count' => 5, 'attempt' => ''];
    }

    public function removeSection(int $index): void
    {
        unset($this->blueprint['sections'][$index]);
        $this->blueprint['sections'] = array_values($this->blueprint['sections']);
    }

    public function saveBlueprint(): void
    {
        $this->validate([
            'blueprint.name' => 'required|string|max:150', 'blueprint.duration_minutes' => 'required|integer|min:15', 'blueprint.avoid_recent_papers' => 'required|integer|min:0',
            'blueprint.sections' => 'required|array|min:1', 'blueprint.sections.*.name' => 'required|string', 'blueprint.sections.*.type' => 'required|string',
            'blueprint.sections.*.marks' => 'required|numeric|min:0.5', 'blueprint.sections.*.count' => 'required|integer|min:1', 'blueprint.sections.*.attempt' => 'nullable|integer|min:1',
        ]);
        $data = [
            'name' => $this->blueprint['name'], 'duration_minutes' => (int) $this->blueprint['duration_minutes'], 'avoid_recent_papers' => (int) $this->blueprint['avoid_recent_papers'],
            'sections' => array_map(fn (array $s) => ['name' => $s['name'], 'type' => $s['type'], 'marks' => (float) $s['marks'], 'count' => (int) $s['count'], 'attempt' => $s['attempt'] !== '' && $s['attempt'] !== null ? (int) $s['attempt'] : null], $this->blueprint['sections']),
        ];

        $this->callApi(fn () => $this->blueprint['id'] ? $this->api()->patch('paper-blueprints/'.$this->blueprint['id'], $data) : $this->api()->post('courses/'.$this->course.'/paper-blueprints', $data),
            fieldMap: ['name' => 'blueprint.name', 'sections' => 'blueprint.sections'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'blueprint-form');
                $this->toast('success', __('Blueprint saved'));
            });
    }

    public function deleteBlueprint(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('paper-blueprints/'.$id), onSuccess: fn () => $this->toast('success', __('Blueprint deleted')));
    }

    public function openGenerate(string $blueprintId): void
    {
        $this->generation = ['blueprint_id' => $blueprintId, 'sets' => 1, 'exam_schedule_id' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'generate');
    }

    public function generate(): void
    {
        $this->validate(['generation.sets' => 'required|integer|min:1|max:4']);

        $this->callApi(fn () => $this->api()->post('paper-blueprints/'.$this->generation['blueprint_id'].'/generate', ['sets' => (int) $this->generation['sets'], 'exam_schedule_id' => $this->generation['exam_schedule_id'] ?: null]), onSuccess: function ($response): void {
            $this->dispatch('close-dialog', 'generate');
            $this->toast('success', __(':n paper(s) generated', ['n' => count((array) $response->data())]));
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blueprints(): array
    {
        return (array) $this->api()->get('courses/'.$this->course.'/paper-blueprints')->data();
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('qbank/options', memoize: true)->data();
        $canSet = $this->can('qbank.papers.set');

        return view('livewire.question-bank.papers', [
            'courses' => app(Lookups::class)->courses(),
            'blueprints' => $this->course !== '' && ($canSet || $this->can('qbank.papers.review')) ? $this->blueprints() : [],
            'rows' => $this->api()->endpoint('question-papers')->page([...$this->activeFilters(null), ...($this->course !== '' ? ['course' => $this->course] : [])], $this->tableQuery()),
            'exams' => $this->course !== '' && $canSet ? collect((array) $this->api()->get('courses/'.$this->course.'/upcoming-exams')->data())->mapWithKeys(fn (array $e) => [$e['id'] => $e['cycle'].' · '.$e['date'].' '.$e['starts_at']])->all() : [],
            'types' => collect($options['types'])->mapWithKeys(fn (array $o) => [$o['value'] => $o['label']])->all(),
            'statuses' => collect($options['paper_statuses'])->mapWithKeys(fn (array $o) => [$o['value'] => $o['label']])->all(),
            'canSet' => $canSet,
        ]);
    }
}
