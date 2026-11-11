<?php

namespace App\Livewire\Feedback;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One complaint's conversation. Staff reply, add internal notes and resolve; the raiser follows up,
 * then closes with a rating once resolved.
 */
#[Layout('layouts::app')]
class ComplaintShow extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $complaintId = '';

    public string $body = '';

    public bool $internal = false;

    public int $rating = 5;

    public function mount(string $complaint): void
    {
        $this->complaintId = $complaint;
    }

    public function assignToMe(): void
    {
        $this->callApi(fn () => $this->api()->post('complaints/'.$this->complaintId.'/assign'), onSuccess: fn () => $this->toast('success', __('Assigned to you')));
    }

    public function send(bool $resolve = false): void
    {
        $this->validate(['body' => 'required|string|max:5000']);
        $staff = $this->complaint()['can_handle'];

        $this->callApi(fn () => $staff
            ? $this->api()->post('complaints/'.$this->complaintId.'/respond', ['body' => $this->body, 'internal' => $this->internal, 'resolve' => $resolve])
            : $this->api()->post('me/complaints/'.$this->complaintId.'/messages', ['body' => $this->body]),
            fieldMap: ['body' => 'body'],
            onSuccess: function () use ($resolve): void {
                $this->body = '';
                $this->internal = false;
                $this->toast('success', $resolve ? __('Marked resolved') : __('Sent'));
            });
    }

    public function close(): void
    {
        $this->callApi(fn () => $this->api()->post('me/complaints/'.$this->complaintId.'/close', ['rating' => $this->rating]), onSuccess: fn () => $this->toast('success', __('Thank you for your feedback')));
    }

    /**
     * @return array<string, mixed>
     */
    private function complaint(): array
    {
        return (array) $this->api()->withoutCache(fn () => $this->api()->get('complaints/'.$this->complaintId))->data();
    }

    /** @var array{summary: string, urgency: string, next_step: string}|null AI triage for the handler */
    public ?array $triage = null;

    public function aiTriage(): void
    {
        $this->callApi(fn () => $this->api()->get('ai/complaints/'.$this->complaintId.'/triage'), onSuccess: fn ($response) => $this->triage = [
            'summary' => (string) $response->data('summary'), 'urgency' => (string) $response->data('urgency'), 'next_step' => (string) $response->data('next_step'),
        ]);
    }

    public function render(): View
    {
        $complaint = $this->complaint();

        return view('livewire.feedback.complaint-show', ['c' => $complaint])->title($complaint['subject']);
    }
}
