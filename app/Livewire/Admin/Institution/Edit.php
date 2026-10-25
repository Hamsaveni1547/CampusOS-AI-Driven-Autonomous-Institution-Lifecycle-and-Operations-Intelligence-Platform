<?php

namespace App\Livewire\Admin\Institution;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The current institution's profile: contact, address, locale and logo.
 */
#[Layout('layouts::app')]
#[Title('Institution profile')]
class Edit extends Component
{
    use InteractsWithApi, WithFileUploads;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    public function mount(): void
    {
        $this->fill(['form' => $this->fromInstitution($this->api()->get('institution')->data())]);
    }

    /**
     * @param  array<string, mixed>  $institution
     * @return array<string, mixed>
     */
    private function fromInstitution(array $institution): array
    {
        return [
            'name' => $institution['name'],
            'short_name' => (string) $institution['short_name'],
            'email' => (string) $institution['email'],
            'phone' => (string) $institution['phone'],
            'website' => (string) $institution['website'],
            'address_line' => (string) $institution['address']['line'],
            'city' => (string) $institution['address']['city'],
            'state' => (string) $institution['address']['state'],
            'country' => (string) $institution['address']['country'],
            'postal_code' => (string) $institution['address']['postal_code'],
            'timezone' => $institution['timezone'],
            'currency' => $institution['currency'],
            'affiliation' => (string) $institution['affiliation'],
            'accreditation_codes' => (string) $institution['accreditation_codes'],
            'established_year' => (string) $institution['established_year'],
        ];
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.email' => 'nullable|email|max:255',
            'form.website' => 'nullable|url|max:255',
            'form.country' => 'required|string|size:2',
            'form.currency' => 'required|string|size:3',
            'form.timezone' => 'required|timezone:all',
            'form.established_year' => 'nullable|integer|min:1800|max:'.now()->year,
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = array_map(fn ($value) => $value === '' ? null : $value, $this->form);
        $data['country'] = strtoupper((string) $data['country']);
        $data['currency'] = strtoupper((string) $data['currency']);
        $data['established_year'] = $data['established_year'] !== null ? (int) $data['established_year'] : null;

        $this->callApi(function () use ($data) {
            if ($this->logo !== null) {
                $data['logo_file_id'] = $this->api()->upload($this->logo, 'institution_logo')['id'];
            }

            return $this->api()->patch('institution', $data);
        }, onSuccess: function (): void {
            $this->logo = null;
            $this->toast('success', __('Institution profile saved'));
        });
    }

    public function removeLogo(): void
    {
        $this->callApi(fn () => $this->api()->patch('institution', ['logo_file_id' => null]), onSuccess: fn () => $this->toast('success', __('Logo removed')));
    }

    public function render(): View
    {
        return view('livewire.admin.institution.edit', ['institution' => $this->api()->get('institution')->data()]);
    }
}
