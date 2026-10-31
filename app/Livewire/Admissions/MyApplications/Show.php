<?php

namespace App\Livewire\Admissions\MyApplications;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The applicant's own application: fill the form, upload documents, submit, accept the offer.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    use InteractsWithApi, WithFileUploads;

    #[Locked]
    public string $applicationId = '';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, TemporaryUploadedFile|null> document type => upload */
    public array $uploads = [];

    public string $reason = '';

    public function mount(string $application): void
    {
        $this->applicationId = $application;
        $this->fillForm($this->application());
    }

    /** @return array<string, mixed> */
    private function application(): array
    {
        return $this->api()->get('me/applications/'.$this->applicationId)->data();
    }

    /**
     * @param  array<string, mixed>  $application
     */
    private function fillForm(array $application): void
    {
        $this->form = [
            'category' => (string) $application['category'],
            'personal' => array_merge(['full_name' => $application['applicant']['name'], 'date_of_birth' => '', 'gender' => '', 'address' => ''], $application['personal']),
            'guardian' => array_merge(['name' => '', 'phone' => ''], $application['guardian']),
            'academic' => array_merge(['percentage' => (string) $application['qualifying_percentage'], 'board' => '', 'year_of_passing' => ''], $application['academic']),
            'entrance' => array_merge(['exam' => '', 'rank' => '', 'allotment_no' => ''], $application['entrance']),
        ];
    }

    /**
     * Form for the API: blanks dropped, numbers cast.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $clean = fn (array $section): array => array_filter($section, fn ($value) => $value !== '' && $value !== null);
        $academic = $clean($this->form['academic']);
        $entrance = $clean($this->form['entrance']);

        foreach (['percentage' => 'float', 'year_of_passing' => 'int'] as $key => $type) {
            if (isset($academic[$key])) {
                $academic[$key] = $type === 'float' ? (float) $academic[$key] : (int) $academic[$key];
            }
        }

        if (isset($entrance['rank'])) {
            $entrance['rank'] = (int) $entrance['rank'];
        }

        return [
            'category' => $this->form['category'] ?: null,
            'personal' => $clean($this->form['personal']),
            'guardian' => $clean($this->form['guardian']),
            'academic' => $academic,
            'entrance' => $entrance,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function fieldMap(): array
    {
        return collect(['personal', 'guardian', 'academic', 'entrance'])
            ->flatMap(fn (string $section) => collect(array_keys($this->form[$section]))->mapWithKeys(fn ($key) => [$section.'.'.$key => 'form.'.$section.'.'.$key]))
            ->put('category', 'form.category')
            ->all();
    }

    public function save(): bool
    {
        $this->validate([
            'form.personal.full_name' => 'required|string|max:150',
            'form.personal.date_of_birth' => 'nullable|date|before:-14 years',
            'form.academic.percentage' => 'nullable|numeric|min:0|max:100',
            'form.academic.year_of_passing' => 'nullable|integer|min:1980',
            'form.guardian.phone' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
            'form.entrance.rank' => 'nullable|integer|min:1',
        ]);

        $response = $this->callApi(fn () => $this->api()->put('me/applications/'.$this->applicationId, $this->payload()), fieldMap: $this->fieldMap());

        if ($response !== null) {
            $this->toast('success', __('Saved'));
        }

        return $response !== null;
    }

    public function updatedUploads(mixed $file, string $type): void
    {
        $this->validate(['uploads.'.$type => 'file|mimes:pdf,jpg,jpeg,png|max:5120']);

        $this->callApi(function () use ($file, $type) {
            $stored = $this->api()->upload($file, 'document');

            return $this->api()->post('me/applications/'.$this->applicationId.'/documents', ['document_type' => $type, 'file_id' => $stored['id']]);
        }, fieldMap: ['file_id' => 'uploads.'.$type, 'document_type' => 'uploads.'.$type], onSuccess: fn () => $this->toast('success', __('Document uploaded')));

        $this->uploads[$type] = null;
    }

    public function submit(): void
    {
        if (! $this->save()) {
            return;
        }

        $this->callApi(fn () => $this->api()->post('me/applications/'.$this->applicationId.'/submit'), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'submit-application');
            $this->toast('success', __('Application submitted'), __('We will email you when it moves forward.'));
        });
    }

    public function acceptOffer(): void
    {
        $this->callApi(fn () => $this->api()->post('me/applications/'.$this->applicationId.'/accept-offer'), onSuccess: fn () => $this->toast('success', __('Offer accepted'), __('Pay the admission fee at the office to complete admission.')));
    }

    public function withdraw(): void
    {
        $this->callApi(fn () => $this->api()->post('me/applications/'.$this->applicationId.'/withdraw', ['reason' => $this->reason ?: null]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'withdraw-application');
            $this->toast('success', __('Application withdrawn'));
        });
    }

    public function render(): View
    {
        $application = $this->application();
        $lookups = app(Lookups::class);

        return view('livewire.admissions.my-applications.show', [
            'application' => $application,
            'quotas' => $lookups->admissionOptions('quotas'),
            'categories' => $lookups->admissionOptions('categories'),
            'uploaded' => collect($application['documents'])->keyBy('document_type'),
        ])->title($application['seat']['programme']['name'] ?? __('Application'));
    }
}
