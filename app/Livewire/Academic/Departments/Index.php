<?php

namespace App\Livewire\Academic\Departments;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\ManagesRecords;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Departments')]
class Index extends Component
{
    use InteractsWithApi, ManagesRecords, WithTable;

    /** @var array<string, mixed>|null Department whose head is being changed */
    public ?array $hodFor = null;

    public string $hodUserId = '';

    protected function defaultFilters(): array
    {
        return ['type' => '', 'status' => ''];
    }

    protected function recordsPath(): string
    {
        return 'departments';
    }

    protected function recordLabel(): string
    {
        return __('Department');
    }

    protected function emptyForm(): array
    {
        return ['code' => '', 'name' => '', 'type' => 'academic', 'status' => 'active', 'description' => '', 'email' => '', 'phone' => '', 'established_year' => ''];
    }

    protected function toForm(array $record): array
    {
        return array_map(fn ($value) => $value ?? '', array_intersect_key($record, $this->emptyForm()));
    }

    protected function toPayload(): array
    {
        $data = $this->nullBlanks($this->form);
        $data['established_year'] = $data['established_year'] !== null ? (int) $data['established_year'] : null;

        return $data;
    }

    protected function formRules(): array
    {
        return [
            'form.code' => 'required|string|max:20|alpha_dash',
            'form.name' => 'required|string|max:150',
            'form.email' => 'nullable|email|max:255',
            'form.established_year' => 'nullable|integer|min:1800|max:'.now()->year,
        ];
    }

    public function openHod(string $id): void
    {
        $this->hodFor = $this->api()->endpoint('departments')->find($id);
        $this->hodUserId = (string) ($this->hodFor['hod']['user_id'] ?? '');
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'hod-form');
    }

    public function assignHod(): void
    {
        $this->validate(['hodUserId' => 'required|string']);

        $this->callApi(fn () => $this->api()->post('departments/'.$this->hodFor['id'].'/hod', ['user_id' => $this->hodUserId]), fieldMap: ['user_id' => 'hodUserId'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'hod-form');
            $this->toast('success', __('Head of department assigned'));
        });
    }

    public function removeHod(): void
    {
        $this->callApi(fn () => $this->api()->delete('departments/'.$this->hodFor['id'].'/hod'), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'hod-form');
            $this->toast('success', __('Head of department removed'));
        });
    }

    public function render(): View
    {
        return view('livewire.academic.departments.index', [
            'departments' => $this->api()->endpoint('departments')->all($this->activeFilters(), memoize: false),
            'canManage' => $this->can('institution.departments.manage'),
            'hodCandidates' => $this->hodFor ? app(Lookups::class)->staffUsers(['department' => $this->hodFor['id'], 'staff_type' => 'teaching']) : [],
            'chips' => $this->filterChips([
                'type' => ['_name' => __('Type'), 'academic' => __('Academic'), 'administrative' => __('Administrative')],
                'status' => ['_name' => __('Status'), 'active' => __('Active'), 'inactive' => __('Inactive')],
            ]),
        ]);
    }
}
