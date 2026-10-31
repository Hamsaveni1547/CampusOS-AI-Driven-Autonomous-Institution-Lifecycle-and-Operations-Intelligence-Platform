<?php

namespace App\Livewire\Assessment;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The student's assignments: what is due, submitting answers, and marks with feedback.
 */
#[Layout('layouts::app')]
#[Title('My assignments')]
class MyAssignments extends Component
{
    use InteractsWithApi, WithFileUploads;

    public string $assignmentId = '';

    public string $title = '';

    public string $text = '';

    public ?TemporaryUploadedFile $answer = null;

    public function open(string $id, string $title, ?string $text): void
    {
        $this->assignmentId = $id;
        $this->title = $title;
        $this->text = $text ?? '';
        $this->answer = null;
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'submit-assignment');
    }

    public function submit(): void
    {
        $this->validate(['text' => 'nullable|required_without:answer|string|max:20000', 'answer' => 'nullable|required_without:text|file|max:20480']);

        $this->callApi(function () {
            $data = ['text' => $this->text ?: null];

            if ($this->answer !== null) {
                $data['file_id'] = $this->api()->upload($this->answer, 'document')['id'];
            }

            return $this->api()->post('me/assignments/'.$this->assignmentId.'/submit', $data);
        }, fieldMap: ['text' => 'text', 'file_id' => 'answer'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'submit-assignment');
            $this->toast('success', __('Submitted'));
        });
    }

    public function render(): View
    {
        return view('livewire.assessment.my-assignments', ['assignments' => (array) $this->api()->get('me/assignments')->data()]);
    }
}
