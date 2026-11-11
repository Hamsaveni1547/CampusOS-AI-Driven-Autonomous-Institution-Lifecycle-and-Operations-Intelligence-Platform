<?php

namespace App\Livewire\Announcements;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One announcement. Recipients acknowledge here; the publisher sees who has read it.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $announcementId = '';

    public function mount(string $announcement): void
    {
        $this->announcementId = $announcement;
    }

    /** @var list<string> AI summary points (language in $summaryLanguage) */
    public array $summary = [];

    public string $summaryLanguage = '';

    public function summarise(string $language): void
    {
        $this->callApi(fn () => $this->api()->get('ai/announcements/'.$this->announcementId.'/summary', ['language' => $language === 'kn' ? 'kn' : 'en']), onSuccess: function ($response): void {
            $this->summary = array_values((array) $response->data('points'));
            $this->summaryLanguage = (string) $response->data('language');
        });
    }

    public function acknowledge(): void
    {
        $this->callApi(fn () => $this->api()->post('announcements/'.$this->announcementId.'/acknowledge'), onSuccess: fn () => $this->toast('success', __('Acknowledged')));
    }

    public function render(): View
    {
        $a = (array) $this->api()->withoutCache(fn () => $this->api()->get('announcements/'.$this->announcementId))->data();
        $stats = ! empty($a['can_manage']) && ($a['status'] ?? '') !== 'draft' ? (array) $this->api()->withoutCache(fn () => $this->api()->get('announcements/'.$this->announcementId.'/read-stats'))->data() : null;

        return view('livewire.announcements.show', ['a' => $a, 'stats' => $stats])->title($a['title']);
    }
}
