<?php

namespace App\Livewire\Admin\Health;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Live status of the database, cache, storage, queue and scheduler.
 */
#[Layout('layouts::app')]
#[Title('System health')]
class Index extends Component
{
    use InteractsWithApi;

    /**
     * API path; the platform monitoring screen overrides it.
     */
    protected function healthPath(): string
    {
        return 'admin/health';
    }

    public function render(): View
    {
        return view('livewire.admin.health.index', [
            'health' => (array) $this->api()->withoutCache(fn () => $this->api()->get($this->healthPath()))->data(),
            'checkedAt' => now()->toIso8601String(),
        ]);
    }
}
