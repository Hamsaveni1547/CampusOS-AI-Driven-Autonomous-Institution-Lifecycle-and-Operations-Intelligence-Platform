<?php

namespace App\Livewire\Assessment;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Assignments of a class's course: create, review submissions and evaluate.
 */
#[Layout('layouts::app')]
class ClassAssignments extends Component
{
    use InteractsWithApi, WithFileUploads;

    #[Locked]
    public string $classId = '';

    #[Url(as: 'assignment', except: '')]
    public string $assignmentId = '';

    /** @var array<string, mixed> */
    public array $form = [];

    public ?TemporaryUploadedFile $attachment = null;

    /** @var array<string, mixed> */
    public array $evaluation = ['id' => '', 'student' => '', 'marks' => '', 'feedback' => ''];

    public function mount(string $class): void
    {
        $this->classId = $class;
    }

    public function open(?string $id = null): void
    {
        $existing = $id ? collect($this->assignments())->firstWhere('id', $id) : null;
        $this->form = [
            'id' => $id,
            'title' => $existing['title'] ?? '',
            'instructions' => $existing['instructions'] ?? '',
            'due_at' => isset($existing['due_at']) ? Carbon::parse($existing['due_at'])->format('Y-m-d\TH:i') : '',
            'max_marks' => $existing['max_marks'] ?? '',
            'allow_late' => $existing['allow_late'] ?? false,
            'assessment_id' => $existing['assessment']['id'] ?? '',
        ];
        $this->attachment = null;
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'assignment-form');
    }

    public function save(): void
    {
        $this->validate([
            'form.title' => 'required|string|max:200',
            'form.due_at' => 'required|date',
            'form.max_marks' => 'required|numeric|min:1',
            'attachment' => 'nullable|file|max:20480',
        ]);

        $this->callApi(function () {
            $data = [
                'title' => $this->form['title'],
                'instructions' => $this->form['instructions'] ?: null,
                'due_at' => Carbon::parse($this->form['due_at'])->toIso8601String(),
                'max_marks' => (float) $this->form['max_marks'],
                'allow_late' => (bool) $this->form['allow_late'],
                'assessment_id' => $this->form['assessment_id'] ?: null,
            ];

            if ($this->attachment !== null) {
                $data['file_id'] = $this->api()->upload($this->attachment, 'document')['id'];
            }

            return $this->form['id']
                ? $this->api()->patch('assignments/'.$this->form['id'], $data)
                : $this->api()->post('offerings/'.$this->offeringId().'/assignments', $data);
        }, fieldMap: ['title' => 'form.title', 'due_at' => 'form.due_at', 'max_marks' => 'form.max_marks', 'assessment_id' => 'form.assessment_id', 'file_id' => 'attachment'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'assignment-form');
            $this->toast('success', __('Assignment saved'));
        });
    }

    public function delete(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('assignments/'.$id), onSuccess: function () use ($id): void {
            if ($this->assignmentId === $id) {
                $this->assignmentId = '';
            }
            $this->toast('success', __('Assignment deleted'));
        });
    }

    public function openEvaluation(string $id, string $student, ?float $marks, ?string $feedback): void
    {
        $this->evaluation = ['id' => $id, 'student' => $student, 'marks' => $marks ?? '', 'feedback' => $feedback ?? ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'evaluate');
    }

    public function evaluate(): void
    {
        $this->validate(['evaluation.marks' => 'required|numeric|min:0', 'evaluation.feedback' => 'nullable|string|max:2000']);

        $this->callApi(fn () => $this->api()->post('assignment-submissions/'.$this->evaluation['id'].'/evaluate', ['marks' => (float) $this->evaluation['marks'], 'feedback' => $this->evaluation['feedback'] ?: null]),
            fieldMap: ['marks' => 'evaluation.marks', 'feedback' => 'evaluation.feedback'],
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'evaluate');
                $this->toast('success', __('Evaluated'));
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function class(): array
    {
        return (array) $this->api()->get('classes/'.$this->classId.'/internal-marks', memoize: true)->data('class');
    }

    private function offeringId(): string
    {
        return $this->class()['offering_id'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function assignments(): array
    {
        return (array) $this->api()->get('offerings/'.$this->offeringId().'/assignments')->data();
    }

    public function render(): View
    {
        $class = $this->class();
        $scheme = (array) $this->api()->get('offerings/'.$class['offering_id'].'/scheme')->data();

        return view('livewire.assessment.class-assignments', [
            'class' => $class,
            'canManage' => $scheme['can_manage'],
            'assignments' => $this->assignments(),
            'detail' => $this->assignmentId !== '' ? (array) $this->api()->withoutCache(fn () => $this->api()->get('assignments/'.$this->assignmentId.'/submissions'))->data() : null,
            'assessments' => collect($scheme['groups'])->flatMap(fn (array $g) => $g['assessments'])->mapWithKeys(fn (array $a) => [$a['id'] => $a['name'].' ('.__('out of :m', ['m' => $a['max_marks'] + 0]).')'])->all(),
        ])->title($class['course']['code'].' '.__('assignments'));
    }
}
