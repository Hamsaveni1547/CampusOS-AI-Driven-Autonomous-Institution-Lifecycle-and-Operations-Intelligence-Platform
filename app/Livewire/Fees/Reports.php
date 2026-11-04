<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Students with dues, and collections for a period.
 */
#[Layout('layouts::app')]
#[Title('Fee reports')]
class Reports extends Component
{
    use InteractsWithApi;

    #[Url(except: 'dues')]
    public string $tab = 'dues';

    #[Url(except: '')]
    public string $programme = '';

    public bool $overdueOnly = false;

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('finance/options', memoize: true)->data();

        return view('livewire.fees.reports', [
            'dues' => $this->tab === 'dues' ? (array) $this->api()->get('finance/reports/dues', ['filter' => array_filter(['programme' => $this->programme, 'overdue' => $this->overdueOnly ? 1 : null])])->data() : null,
            'collections' => $this->tab === 'collections' ? (array) $this->api()->get('finance/reports/collections', ['from' => $this->from, 'to' => $this->to])->data() : null,
            'programmes' => app(Lookups::class)->programmes(),
            'modes' => collect($options['modes'])->mapWithKeys(fn (array $m) => [$m['value'] => $m['label']])->all(),
        ]);
    }
}
