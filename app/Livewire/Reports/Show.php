<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Runs one report with its filters, and downloads it as CSV.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi;

    #[Locked]
    public string $key = '';

    #[Url(except: '')]
    public string $programme = '';

    #[Url(except: '')]
    public string $examCycle = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function mount(string $key): void
    {
        $this->key = $key;
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function report(): array
    {
        return (array) $this->api()->withoutCache(fn () => $this->api()->get('reports/'.$this->key, ['filter' => array_filter([
            'programme' => $this->programme, 'exam_cycle' => $this->examCycle, 'from' => $this->from, 'to' => $this->to,
        ])]))->data();
    }

    public function download(): StreamedResponse
    {
        $report = $this->report();
        $columns = $report['columns'];

        return response()->streamDownload(function () use ($report, $columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_column($columns, 'label'));
            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map(fn (array $c) => $row[$c['key']] ?? '', $columns));
            }
            fclose($out);
        }, $this->key.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        $report = $this->report();

        return view('livewire.reports.show', [
            'report' => $report,
            'programmes' => in_array('programme', $report['filters'], true) ? app(Lookups::class)->programmes() : [],
            'cycles' => in_array('exam_cycle', $report['filters'], true) ? collect((array) $this->api()->get('exam-cycles', memoize: true)->data())->mapWithKeys(fn (array $c) => [$c['id'] => $c['name']])->all() : [],
        ])->title($report['label']);
    }
}
