<?php

namespace App\Livewire\Platform;

use App\Livewire\Admin\Settings\Index as InstitutionSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Platform-wide defaults that every institution inherits unless it overrides them.
 */
#[Layout('layouts::app')]
#[Title('Platform settings')]
class Settings extends InstitutionSettings
{
    protected function basePath(): string
    {
        return 'platform/settings';
    }

    protected function managePermission(): ?string
    {
        return null;
    }

    protected function heading(): array
    {
        return ['title' => __('Platform settings'), 'description' => __('Defaults for every institution. An institution can override them in its own settings.')];
    }
}
