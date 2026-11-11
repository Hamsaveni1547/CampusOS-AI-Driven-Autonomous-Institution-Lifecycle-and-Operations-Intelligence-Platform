<?php

namespace App\Livewire\Platform\Access;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The module catalogue, and switching modules on or off for one institution.
 */
#[Layout('layouts::app')]
#[Title('Modules')]
class Modules extends Component
{
    use InteractsWithApi;

    #[Url(except: '')]
    public string $institution = '';

    /** @var array<string, bool> module => enabled, for the chosen institution */
    public array $enabled = [];

    public function updatedInstitution(): void
    {
        $this->load();
    }

    public function mount(): void
    {
        $this->load();
    }

    private function load(): void
    {
        $this->enabled = $this->institution === '' ? [] : collect((array) $this->api()->withoutCache(fn () => $this->api()->get('platform/institutions/'.$this->institution.'/modules'))->data())
            ->mapWithKeys(fn (array $m) => [$m['key'] => (bool) $m['enabled']])->all();
    }

    public function save(): void
    {
        $this->callApi(fn () => $this->api()->put('platform/institutions/'.$this->institution.'/modules', ['modules' => array_map('boolval', $this->enabled)]), onSuccess: function (): void {
            $this->load();
            $this->toast('success', __('Modules updated'), __('Menus update the next time people open a page.'));
        });
    }

    public function render(): View
    {
        return view('livewire.platform.access.modules', [
            'modules' => (array) $this->api()->get('platform/access/modules', memoize: true)->data(),
            'institutions' => collect($this->api()->endpoint('platform/institutions')->all())->mapWithKeys(fn (array $i) => [$i['id'] => $i['name']])->all(),
        ]);
    }
}
