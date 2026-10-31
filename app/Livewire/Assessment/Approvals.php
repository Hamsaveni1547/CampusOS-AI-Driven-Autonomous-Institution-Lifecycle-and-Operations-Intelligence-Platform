<?php

namespace App\Livewire\Assessment;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Internal marks waiting for verification or locking, and mark corrections to decide.
 */
#[Layout('layouts::app')]
#[Title('Internal marks approval')]
class Approvals extends Component
{
    use InteractsWithApi, WithTable;

    #[Url(except: 'sheets')]
    public string $tab = 'sheets';

    #[Url(as: 'status', except: 'submitted')]
    public string $sheetStatus = 'submitted';

    public ?string $rejectId = null;

    public string $remarks = '';

    protected function defaultFilters(): array
    {
        return ['status' => 'pending'];
    }

    public function verify(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('internal-mark-sheets/'.$id.'/verify', ['approve' => true]), onSuccess: fn () => $this->toast('success', __('Internal marks verified')));
    }

    public function lock(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('internal-mark-sheets/'.$id.'/lock'), onSuccess: fn () => $this->toast('success', __('Internal marks locked')));
    }

    public function approve(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('mark-corrections/'.$id.'/decide', ['approve' => true]), onSuccess: fn () => $this->toast('success', __('Mark corrected')));
    }

    public function askReject(string $id): void
    {
        $this->rejectId = $id;
        $this->remarks = '';
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'reject-mark-correction');
    }

    public function reject(): void
    {
        $this->validate(['remarks' => 'required|string|max:500']);

        $this->callApi(fn () => $this->api()->post('mark-corrections/'.$this->rejectId.'/decide', ['approve' => false, 'remarks' => $this->remarks]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'reject-mark-correction');
            $this->toast('success', __('Request rejected'));
        });
    }

    public function render(): View
    {
        $options = (array) $this->api()->get('assessment/options', memoize: true)->data();

        return view('livewire.assessment.approvals', [
            'sheets' => $this->tab === 'sheets' ? (array) $this->api()->withoutCache(fn () => $this->api()->get('internal-mark-sheets', ['status' => $this->sheetStatus]))->data() : [],
            'rows' => $this->tab === 'corrections' ? $this->api()->endpoint('mark-corrections')->page($this->activeFilters(null), $this->tableQuery()) : null,
            'sheetStatuses' => collect($options['sheet_statuses'])->reject(fn (array $s) => $s['value'] === 'draft')->mapWithKeys(fn (array $s) => [$s['value'] => $s['label']])->all(),
            'markStatuses' => collect($options['mark_statuses'])->mapWithKeys(fn (array $s) => [$s['value'] => $s['label']])->all(),
        ]);
    }
}
