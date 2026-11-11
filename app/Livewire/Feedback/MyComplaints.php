<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The user's complaints, and raising a new one (optionally anonymous).
 */
#[Layout('layouts::app')]
#[Title('My complaints')]
class MyComplaints extends Component
{
    use InteractsWithApi;
    use WithFileUploads;

    /** @var array<string, mixed> */
    public array $form = ['category_id' => '', 'subject' => '', 'description' => '', 'anonymous' => false];

    /** @var array<int, TemporaryUploadedFile> photos or documents to attach */
    public array $files = [];

    public function open(): void
    {
        $this->form = ['category_id' => '', 'subject' => '', 'description' => '', 'anonymous' => false];
        $this->files = [];
        $this->suggestion = null;
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'raise-complaint');
    }

    /** @var array{category: string|null, urgency: string, reason: string}|null AI suggestion */
    public ?array $suggestion = null;

    /**
     * AI picks the category (and urgency) from what the person wrote; they can still change it.
     */
    public function suggestCategory(): void
    {
        $this->validate(['form.subject' => 'required|string|max:200', 'form.description' => 'required|string|min:10|max:5000']);

        $this->callApi(fn () => $this->api()->post('ai/complaints/suggest-category', ['subject' => $this->form['subject'], 'description' => $this->form['description']]), onSuccess: function ($response): void {
            if ($response->data('category_id')) {
                $this->form['category_id'] = (string) $response->data('category_id');
            }
            $this->suggestion = ['category' => $response->data('category'), 'urgency' => (string) $response->data('urgency'), 'reason' => (string) $response->data('reason')];
        });
    }

    public function raise(): void
    {
        $this->validate(['form.category_id' => 'required|string', 'form.subject' => 'required|string|max:200', 'form.description' => 'required|string|max:5000', 'files' => 'array|max:5', 'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,pdf,doc,docx']);

        $this->callApi(fn () => $this->api()->post('me/complaints', [...$this->form, 'anonymous' => (bool) $this->form['anonymous'], 'attachment_ids' => $this->uploadFiles()]),
            fieldMap: ['category_id' => 'form.category_id', 'subject' => 'form.subject', 'description' => 'form.description'],
            onSuccess: fn ($response) => $this->redirectRoute('complaints.show', $response->data('id'), navigate: true));
    }

    /**
     * @return list<string> ids of the uploaded files
     */
    private function uploadFiles(): array
    {
        return array_values(array_map(fn (TemporaryUploadedFile $file): string => (string) $this->api()->upload($file, 'attachment')['id'], $this->files));
    }

    public function render(): View
    {
        return view('livewire.feedback.my-complaints', [
            'complaints' => (array) $this->api()->withoutCache(fn () => $this->api()->get('me/complaints'))->data(),
            'categories' => collect((array) $this->api()->get('complaint-categories', memoize: true)->data())->where('status', 'active')->mapWithKeys(fn (array $c) => [$c['id'] => $c['name']])->all(),
        ]);
    }
}
