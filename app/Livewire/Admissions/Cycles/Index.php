<?php

namespace App\Livewire\Admissions\Cycles;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\ManagesRecords;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Admission cycles: dates, fees, required documents. Seats are set on the cycle page.
 */
#[Layout('layouts::app')]
#[Title('Admission cycles')]
class Index extends Component
{
    use InteractsWithApi, ManagesRecords, WithTable;

    protected function defaultFilters(): array
    {
        return ['status' => ''];
    }

    protected function recordsPath(): string
    {
        return 'admission-cycles';
    }

    protected function recordLabel(): string
    {
        return __('Admission cycle');
    }

    protected function emptyForm(): array
    {
        return ['academic_year_id' => '', 'name' => '', 'opens_on' => '', 'closes_on' => '', 'application_fee' => '0', 'offer_valid_days' => '7', 'required_documents' => '', 'instructions' => ''];
    }

    protected function toForm(array $record): array
    {
        return [
            'academic_year_id' => (string) ($record['academic_year']['id'] ?? ''),
            'name' => $record['name'],
            'opens_on' => $record['opens_on'],
            'closes_on' => $record['closes_on'],
            'application_fee' => (string) $record['application_fee'],
            'offer_valid_days' => (string) $record['offer_valid_days'],
            'required_documents' => implode(', ', $record['required_documents']),
            'instructions' => (string) $record['instructions'],
        ];
    }

    protected function toPayload(): array
    {
        $data = $this->nullBlanks($this->form);
        $data['application_fee'] = (float) ($data['application_fee'] ?? 0);
        $data['offer_valid_days'] = (int) ($data['offer_valid_days'] ?? 7);
        $data['required_documents'] = collect(explode(',', (string) $this->form['required_documents']))
            ->map(fn (string $type) => str((string) $type)->trim()->snake()->value())
            ->filter()->unique()->values()->all();

        return $data;
    }

    protected function formRules(): array
    {
        return [
            'form.academic_year_id' => 'required|string',
            'form.name' => 'required|string|max:150',
            'form.opens_on' => 'required|date',
            'form.closes_on' => 'required|date|after_or_equal:form.opens_on',
            'form.application_fee' => 'nullable|numeric|min:0',
            'form.offer_valid_days' => 'nullable|integer|min:1|max:60',
        ];
    }

    public function render(): View
    {
        return view('livewire.admissions.cycles.index', [
            'cycles' => $this->api()->endpoint('admission-cycles')->all($this->activeFilters(null), memoize: false),
            'years' => $this->form !== [] ? app(Lookups::class)->academicYears() : [],
            'chips' => $this->filterChips(['status' => ['_name' => __('Status'), 'draft' => __('Draft'), 'open' => __('Open'), 'closed' => __('Closed')]]),
        ]);
    }
}
