<?php

namespace App\Livewire\Library;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Circulation desk: look up a member, issue and return by accession number, overdue books and fines.
 */
#[Layout('layouts::app')]
#[Title('Circulation')]
class Desk extends Component
{
    use InteractsWithApi, WithTable;

    public string $member = '';

    public string $issueAccession = '';

    public string $returnAccession = '';

    /** @var array<string, mixed>|null */
    public ?array $account = null;

    public ?string $waiveId = null;

    public string $note = '';

    protected function defaultFilters(): array
    {
        return ['state' => 'overdue'];
    }

    public function lookup(): void
    {
        $this->validate(['member' => 'required|string']);
        $this->callApi(fn () => $this->api()->withoutCache(fn () => $this->api()->get('library/member', ['member' => trim($this->member)])), fieldMap: ['member' => 'member'], onSuccess: fn ($response) => $this->account = (array) $response->data());
    }

    public function issue(): void
    {
        $this->validate(['issueAccession' => 'required|string', 'member' => 'required|string']);

        $this->callApi(fn () => $this->api()->post('library/issue', ['accession_no' => trim($this->issueAccession), 'member' => trim($this->member)]), fieldMap: ['accession_no' => 'issueAccession', 'member' => 'member'], onSuccess: function ($response): void {
            $this->toast('success', __('Issued; due :d', ['d' => $response->data('due_on')]));
            $this->issueAccession = '';
            $this->lookup();
        });
    }

    public function returnBook(): void
    {
        $this->validate(['returnAccession' => 'required|string']);

        $this->callApi(fn () => $this->api()->post('library/return', ['accession_no' => trim($this->returnAccession)]), fieldMap: ['accession_no' => 'returnAccession'], onSuccess: function ($response): void {
            $fine = (float) $response->data('fine');
            $this->toast($fine > 0 ? 'warning' : 'success', $fine > 0 ? __('Returned late: fine ₹:f', ['f' => number_format($fine, 2)]) : __('Returned'));
            $this->returnAccession = '';

            if ($this->account !== null) {
                $this->lookup();
            }
        });
    }

    public function collect(string $loanId): void
    {
        $this->callApi(fn () => $this->api()->post('library/loans/'.$loanId.'/fine', ['how' => 'paid']), onSuccess: function (): void {
            $this->toast('success', __('Fine collected'));

            if ($this->account !== null) {
                $this->lookup();
            }
        });
    }

    public function askWaive(string $loanId): void
    {
        $this->waiveId = $loanId;
        $this->note = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'waive-fine');
    }

    public function waive(): void
    {
        $this->validate(['note' => 'required|string|max:200']);

        $this->callApi(fn () => $this->api()->post('library/loans/'.$this->waiveId.'/fine', ['how' => 'waived', 'note' => $this->note]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'waive-fine');
            $this->toast('success', __('Fine waived'));
        });
    }

    public function render(): View
    {
        return view('livewire.library.desk', [
            'summary' => (array) $this->api()->withoutCache(fn () => $this->api()->get('library/summary'))->data(),
            'rows' => $this->api()->endpoint('library/loans')->page($this->activeFilters(), $this->tableQuery()),
        ]);
    }
}
