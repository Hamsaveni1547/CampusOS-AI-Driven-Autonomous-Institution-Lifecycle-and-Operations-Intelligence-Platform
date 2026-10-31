<?php

namespace App\Livewire\Assessment;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * One class's internal assessment: scheme, marks entry, internal marks with approval, and outcome attainment.
 */
#[Layout('layouts::app')]
class ClassMarks extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $classId = '';

    #[Url(except: 'marks')]
    public string $tab = 'marks';

    #[Url(as: 'assessment', except: '')]
    public string $assessmentId = '';

    /** @var array<string, array{marks: string|float|null, status: string}> */
    public array $entries = [];

    /** @var array<string, mixed> */
    public array $group = [];

    /** @var array<string, mixed> */
    public array $assessment = [];

    /** @var array<string, string> */
    public array $outcome = ['code' => '', 'description' => '', 'bloom_level' => ''];

    /** @var array<string, mixed> */
    public array $correction = [];

    public string $remarks = '';

    public function mount(string $class): void
    {
        $this->classId = $class;
    }

    public function updatedAssessmentId(): void
    {
        $this->entries = [];
    }

    // ----- scheme ----------------------------------------------------------

    public function openGroup(?string $id = null): void
    {
        $existing = $id ? collect($this->scheme()['groups'])->firstWhere('id', $id) : null;
        $this->group = ['id' => $id, 'name' => $existing['name'] ?? '', 'best_of' => $existing['best_of'] ?? '', 'weight' => $existing['weight'] ?? ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'group-form');
    }

    public function saveGroup(): void
    {
        $this->validate(['group.name' => 'required|string|max:100', 'group.best_of' => 'nullable|integer|min:1', 'group.weight' => 'required|numeric|min:0']);
        $data = ['name' => $this->group['name'], 'best_of' => $this->group['best_of'] !== '' ? (int) $this->group['best_of'] : null, 'weight' => (float) $this->group['weight']];

        $this->callApi(fn () => $this->group['id']
            ? $this->api()->patch('assessment-groups/'.$this->group['id'], $data)
            : $this->api()->post('offerings/'.$this->offeringId().'/assessment-groups', $data),
            fieldMap: ['name' => 'group.name', 'best_of' => 'group.best_of', 'weight' => 'group.weight'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'group-form');
                $this->toast('success', __('Part saved'));
            });
    }

    public function deleteGroup(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('assessment-groups/'.$id), onSuccess: fn () => $this->toast('success', __('Part deleted')));
    }

    public function openAssessment(string $groupId, ?string $id = null): void
    {
        $existing = $id ? collect($this->scheme()['groups'])->flatMap(fn (array $g) => $g['assessments'])->firstWhere('id', $id) : null;
        $this->assessment = [
            'group_id' => $groupId, 'id' => $id,
            'name' => $existing['name'] ?? '', 'kind' => $existing['kind'] ?? 'test', 'held_on' => $existing['held_on'] ?? '',
            'max_marks' => $existing['max_marks'] ?? '', 'outcome_codes' => $existing['outcome_codes'] ?? [],
        ];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'assessment-form');
    }

    public function saveAssessment(): void
    {
        $this->validate(['assessment.name' => 'required|string|max:100', 'assessment.kind' => 'required|string', 'assessment.held_on' => 'nullable|date', 'assessment.max_marks' => 'required|numeric|min:1']);
        $data = [
            'name' => $this->assessment['name'], 'kind' => $this->assessment['kind'], 'held_on' => $this->assessment['held_on'] ?: null,
            'max_marks' => (float) $this->assessment['max_marks'], 'outcome_codes' => array_values($this->assessment['outcome_codes']),
        ];

        $this->callApi(fn () => $this->assessment['id']
            ? $this->api()->patch('assessments/'.$this->assessment['id'], $data)
            : $this->api()->post('assessment-groups/'.$this->assessment['group_id'].'/assessments', $data),
            fieldMap: ['name' => 'assessment.name', 'kind' => 'assessment.kind', 'held_on' => 'assessment.held_on', 'max_marks' => 'assessment.max_marks', 'outcome_codes' => 'assessment.outcome_codes'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'assessment-form');
                $this->toast('success', __('Assessment saved'));
            });
    }

    public function deleteAssessment(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('assessments/'.$id), onSuccess: fn () => $this->toast('success', __('Assessment deleted')));
    }

    // ----- outcomes --------------------------------------------------------

    public function addOutcome(): void
    {
        $this->validate(['outcome.code' => 'required|string|max:10', 'outcome.description' => 'required|string|max:500']);
        $data = ['code' => $this->outcome['code'], 'description' => $this->outcome['description'], 'bloom_level' => $this->outcome['bloom_level'] ?: null];

        $this->callApi(fn () => $this->api()->post('courses/'.$this->scheme()['offering']['course']['id'].'/outcomes', $data), fieldMap: ['code' => 'outcome.code', 'description' => 'outcome.description'], onSuccess: function (): void {
            $this->outcome = ['code' => '', 'description' => '', 'bloom_level' => ''];
            $this->toast('success', __('Outcome added'));
        });
    }

    public function deleteOutcome(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('course-outcomes/'.$id), onSuccess: fn () => $this->toast('success', __('Outcome deleted')));
    }

    // ----- marks -----------------------------------------------------------

    public function saveMarks(): void
    {
        $this->validate(['entries.*.marks' => 'nullable|numeric|min:0', 'entries.*.status' => 'required|string']);
        $marks = collect($this->entries)->map(fn (array $e) => ['marks' => $e['status'] === 'present' && $e['marks'] !== '' && $e['marks'] !== null ? (float) $e['marks'] : null, 'status' => $e['status']])->all();

        $this->callApi(fn () => $this->api()->put('assessments/'.$this->assessmentId.'/marks', ['class' => $this->classId, 'marks' => $marks]), onSuccess: function (): void {
            $this->entries = [];
            $this->toast('success', __('Marks saved'));
        });
    }

    public function compute(): void
    {
        $this->callApi(fn () => $this->api()->post('classes/'.$this->classId.'/internal-marks/compute'), onSuccess: fn () => $this->toast('success', __('Internal marks calculated')));
    }

    public function submit(): void
    {
        $this->callApi(fn () => $this->api()->post('classes/'.$this->classId.'/internal-marks/submit'), onSuccess: fn () => $this->toast('success', __('Submitted for verification')));
    }

    public function verify(): void
    {
        $this->callApi(fn () => $this->api()->post('internal-mark-sheets/'.$this->sheet()['id'].'/verify', ['approve' => true]), onSuccess: fn () => $this->toast('success', __('Internal marks verified')));
    }

    public function askSendBack(): void
    {
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'send-back');
    }

    public function sendBack(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('internal-mark-sheets/'.$this->sheet()['id'].'/verify', ['approve' => false, 'remarks' => $this->remarks]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'send-back');
            $this->toast('success', __('Sent back to the teacher'));
        });
    }

    public function lock(): void
    {
        $this->callApi(fn () => $this->api()->post('internal-mark-sheets/'.$this->sheet()['id'].'/lock'), onSuccess: fn () => $this->toast('success', __('Internal marks locked')));
    }

    // ----- corrections -----------------------------------------------------

    public function openCorrection(int $markId, string $student, ?float $marks, string $status): void
    {
        $this->correction = ['mark_id' => $markId, 'student' => $student, 'marks' => $marks, 'status' => $status, 'reason' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'mark-correction');
    }

    public function requestCorrection(): void
    {
        $this->validate(['correction.marks' => 'nullable|numeric|min:0', 'correction.status' => 'required|string', 'correction.reason' => 'required|string|max:500']);
        $data = ['mark_id' => $this->correction['mark_id'], 'marks' => $this->correction['status'] === 'present' ? (float) $this->correction['marks'] : null, 'status' => $this->correction['status'], 'reason' => $this->correction['reason']];

        $this->callApi(fn () => $this->api()->post('mark-corrections', $data), fieldMap: ['marks' => 'correction.marks', 'status' => 'correction.status', 'reason' => 'correction.reason'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'mark-correction');
            $this->toast('success', __('Correction requested'));
        });
    }

    // ----- data ------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function sheet(): array
    {
        return (array) $this->api()->get('classes/'.$this->classId.'/internal-marks')->data();
    }

    private function offeringId(): string
    {
        return $this->sheet()['class']['offering_id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function scheme(): array
    {
        return (array) $this->api()->get('offerings/'.$this->offeringId().'/scheme')->data();
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('assessment/options', memoize: true)->data();
        $sheet = $this->sheet();
        $scheme = $this->scheme();
        $assessments = collect($scheme['groups'])->flatMap(fn (array $g) => $g['assessments']);

        if ($this->assessmentId === '' && $assessments->isNotEmpty()) {
            $this->assessmentId = $assessments->first()['id'];
        }

        $marks = null;

        if ($this->tab === 'marks' && $assessments->contains('id', $this->assessmentId)) {
            $marks = (array) $this->api()->withoutCache(fn () => $this->api()->get('assessments/'.$this->assessmentId.'/marks', ['class' => $this->classId]))->data();

            if ($this->entries === []) {
                $this->entries = collect($marks['students'])->mapWithKeys(fn (array $s) => [$s['id'] => ['marks' => $s['marks'], 'status' => $s['status'] ?? 'present']])->all();
            }
        }

        $outcomes = $this->tab === 'outcomes' ? (array) $this->api()->get('courses/'.$scheme['offering']['course']['id'].'/outcomes')->data() : [];

        return view('livewire.assessment.class-marks', [
            'sheet' => $sheet,
            'scheme' => $scheme,
            'assessments' => $assessments->mapWithKeys(fn (array $a) => [$a['id'] => $a['name']])->all(),
            'marks' => $marks,
            'outcomes' => $outcomes,
            'attainment' => $this->tab === 'outcomes' ? (array) $this->api()->get('classes/'.$this->classId.'/attainment')->data('outcomes', []) : [],
            'outcomeCodes' => collect($this->tab === 'scheme' ? (array) $this->api()->get('courses/'.$scheme['offering']['course']['id'].'/outcomes')->data() : $outcomes)->mapWithKeys(fn (array $o) => [$o['code'] => $o['code'].' · '.$o['description']])->all(),
            'kinds' => collect($options['kinds'])->mapWithKeys(fn (array $k) => [$k['value'] => $k['label']])->all(),
            'markStatuses' => collect($options['mark_statuses'])->mapWithKeys(fn (array $k) => [$k['value'] => $k['label']])->all(),
            'blooms' => collect($options['bloom_levels'])->mapWithKeys(fn (array $k) => [$k['value'] => $k['label']])->all(),
            'target' => $options['co_target_percent'],
        ])->title($sheet['class']['course']['code'].' '.__('marks'));
    }
}
