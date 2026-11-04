<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The user's complaints, and raising a new one (optionally anonymous).
 */
#[Layout('layouts::app')]
#[Title('My complaints')]
class MyComplaints extends Component
{
    use InteractsWithApi;

    /** @var array<string, mixed> */
    public array $form = ['category_id' => '', 'subject' => '', 'description' => '', 'anonymous' => false];

    public function open(): void
    {
        $this->form = ['category_id' => '', 'subject' => '', 'description' => '', 'anonymous' => false];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'raise-complaint');
    }

    public function raise(): void
    {
        $this->validate(['form.category_id' => 'required|string', 'form.subject' => 'required|string|max:200', 'form.description' => 'required|string|max:5000']);

        $this->callApi(fn () => $this->api()->post('me/complaints', [...$this->form, 'anonymous' => (bool) $this->form['anonymous']]),
            fieldMap: ['category_id' => 'form.category_id', 'subject' => 'form.subject', 'description' => 'form.description'],
            onSuccess: fn ($response) => $this->redirectRoute('complaints.show', $response->data('id'), navigate: true));
    }

    public function render(): View
    {
        return view('livewire.feedback.my-complaints', [
            'complaints' => (array) $this->api()->withoutCache(fn () => $this->api()->get('me/complaints'))->data(),
            'categories' => collect((array) $this->api()->get('complaint-categories', memoize: true)->data())->where('status', 'active')->mapWithKeys(fn (array $c) => [$c['id'] => $c['name']])->all(),
        ]);
    }
}
