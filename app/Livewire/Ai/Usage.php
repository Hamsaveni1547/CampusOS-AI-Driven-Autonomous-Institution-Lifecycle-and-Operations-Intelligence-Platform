<?php

namespace App\Livewire\Ai;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * How much AI the college uses: calls per day, by feature and provider, tokens and failures.
 */
#[Layout('layouts::app')]
#[Title('AI usage')]
class Usage extends Component
{
    use InteractsWithApi;

    #[Url(except: 14)]
    public int $days = 14;

    public function render(): View
    {
        return view('livewire.ai.usage', ['u' => (array) $this->api()->withoutCache(fn () => $this->api()->get('ai/usage', ['days' => $this->days]))->data()]);
    }
}
