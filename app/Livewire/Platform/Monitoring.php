<?php

namespace App\Livewire\Platform;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Platform health, queue sizes and failed background jobs (retry or forget).
 */
#[Layout('layouts::app')]
#[Title('Monitoring')]
class Monitoring extends Component
{
    use InteractsWithApi;

    public function retry(string $jobId): void
    {
        $this->callApi(fn () => $this->api()->post('platform/monitoring/failed-jobs/'.$jobId.'/retry'), onSuccess: fn () => $this->toast('success', __('Job sent back to the queue')));
    }

    public function forget(string $jobId): void
    {
        $this->callApi(fn () => $this->api()->delete('platform/monitoring/failed-jobs/'.$jobId), onSuccess: fn () => $this->toast('success', __('Failed job removed')));
    }

    public function render(): View
    {
        $data = $this->api()->withoutCache(fn () => $this->api()->getMany([
            'health' => 'platform/monitoring/health',
            'queues' => 'platform/monitoring/queues',
            'failed' => ['platform/monitoring/failed-jobs', ['limit' => 100]],
        ]));

        return view('livewire.platform.monitoring', [
            'health' => (array) $data['health']->data(),
            'queues' => (array) $data['queues']->data(),
            'failed' => (array) $data['failed']->data(),
            'checkedAt' => now()->toIso8601String(),
        ]);
    }
}
