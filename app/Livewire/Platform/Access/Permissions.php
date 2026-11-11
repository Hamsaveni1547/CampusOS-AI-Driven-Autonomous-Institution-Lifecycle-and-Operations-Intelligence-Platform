<?php

namespace App\Livewire\Platform\Access;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Every permission, grouped by module, with the built-in roles that have it.
 */
#[Layout('layouts::app')]
#[Title('Permissions')]
class Permissions extends Component
{
    use InteractsWithApi;

    #[Url(except: '')]
    public string $search = '';

    public function render(): View
    {
        $roles = collect((array) $this->api()->get('platform/access/roles', memoize: true)->data())->mapWithKeys(fn (array $r) => [$r['name'] => $r['display_name']]);
        $term = mb_strtolower(trim($this->search));

        $groups = collect((array) $this->api()->get('platform/access/permissions', memoize: true)->data())
            ->map(fn (array $g) => [...$g, 'permissions' => collect($g['permissions'])->filter(fn (array $p) => $term === '' || str_contains(mb_strtolower($p['name'].' '.$p['description'].' '.$g['label']), $term))->values()->all()])
            ->filter(fn (array $g) => $g['permissions'] !== [])->values()->all();

        return view('livewire.platform.access.permissions', ['groups' => $groups, 'roleNames' => $roles->all()]);
    }
}
