<?php

namespace App\Livewire\Account;

use App\Auth\SessionAuth;
use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * My profile: name, language, timezone, photo.
 */
#[Layout('layouts::app')]
#[Title('My profile')]
class Profile extends Component
{
    use InteractsWithApi, WithFileUploads;

    public string $name = '';

    public string $locale = 'en';

    public string $timezone = '';

    /**
     * @var TemporaryUploadedFile|null
     */
    public $photo = null;

    public function mount(): void
    {
        $me = $this->api()->me()->profile();
        app(SessionAuth::class)->storeMe($me);

        $this->name = (string) $me['name'];
        $this->locale = (string) ($me['locale'] ?? 'en');
        $this->timezone = (string) ($me['timezone'] ?? '');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'locale' => ['required', 'in:en,kn,hi,ta,te,ml,mr'],
            'timezone' => ['nullable', 'timezone:all'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->callApi(function (): array {
            $data = ['name' => trim($this->name), 'locale' => $this->locale, 'timezone' => $this->timezone !== '' ? $this->timezone : null];

            if ($this->photo instanceof TemporaryUploadedFile) {
                $data['avatar_file_id'] = $this->api()->upload($this->photo, 'avatar')['id'];
            }

            return $this->api()->me()->update($data);
        }, onSuccess: function (array $me): void {
            app(SessionAuth::class)->storeMe($me);
            $this->photo = null;
            $this->toast('success', __('Profile saved'));
        });
    }

    public function render(): View
    {
        return view('livewire.account.profile', [
            'me' => app(SessionAuth::class)->user(),
            'timezones' => collect(['Asia/Kolkata', 'Asia/Dubai', 'Asia/Singapore', 'Europe/London', 'America/New_York', 'UTC'])
                ->mapWithKeys(fn (string $zone) => [$zone => str_replace('_', ' ', $zone)])->all(),
        ]);
    }
}
