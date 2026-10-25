<?php

namespace App\Livewire\Hr\Staff;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Staff')]
class Index extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @return array<string, string> */
    public static function employmentTypes(): array
    {
        return ['permanent' => __('Permanent'), 'probation' => __('Probation'), 'contract' => __('Contract'), 'visiting' => __('Visiting'), 'guest' => __('Guest')];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return ['active' => __('Active'), 'on_leave' => __('On leave'), 'resigned' => __('Resigned'), 'retired' => __('Retired'), 'terminated' => __('Terminated')];
    }

    /** @return array<string, string> */
    public static function staffTypes(): array
    {
        return ['teaching' => __('Teaching'), 'non_teaching' => __('Non-teaching')];
    }

    protected function defaultFilters(): array
    {
        return ['department' => '', 'designation' => '', 'staff_type' => '', 'employment_type' => '', 'status' => ''];
    }

    /** @return array<string, mixed> */
    private function emptyForm(): array
    {
        return ['name' => '', 'email' => '', 'mobile' => '', 'employee_code' => '', 'staff_type' => 'teaching', 'employment_type' => 'permanent', 'department_id' => '', 'designation_id' => '', 'joined_on' => now()->toDateString(), 'contract_ends_on' => '', 'send_invite' => true];
    }

    public function openCreate(): void
    {
        $this->form = $this->emptyForm();
        $this->form['department_id'] = (string) ($this->filters['department'] ?? '');
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'staff-form');
    }

    public function create(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:150',
            'form.email' => 'required|email|max:255',
            'form.mobile' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
            'form.employee_code' => 'nullable|string|max:30|alpha_dash',
            'form.joined_on' => 'required|date',
            'form.contract_ends_on' => 'nullable|date|after:form.joined_on',
        ]);

        $data = array_map(fn ($value) => $value === '' ? null : $value, $this->form);
        $data['send_invite'] = (bool) $this->form['send_invite'];

        $response = $this->callApi(fn () => $this->api()->endpoint('employees')->create(array_filter($data, fn ($v) => $v !== null)));

        if ($response !== null) {
            $this->dispatch('close-dialog', 'staff-form');
            $this->toast('success', __('Staff member added'));
            $this->redirectRoute('staff.show', $response->data('id'), navigate: true);
        }
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $departments = $lookups->departments(false);
        $designations = collect($this->api()->endpoint('designations')->all())->mapWithKeys(fn ($d) => [$d['id'] => $d['name']])->all();

        return view('livewire.hr.staff.index', [
            'rows' => $this->api()->endpoint('employees')->page($this->activeFilters(), $this->tableQuery()),
            'departments' => $departments,
            'designations' => $designations,
            'formDesignations' => $this->form !== [] ? $lookups->designations($this->form['staff_type'] ?? null) : [],
            'canManage' => $this->can('hr.employees.manage'),
            'chips' => $this->filterChips([
                'department' => ['_name' => __('Department')] + $departments,
                'designation' => ['_name' => __('Designation')] + $designations,
                'staff_type' => ['_name' => __('Type')] + self::staffTypes(),
                'employment_type' => ['_name' => __('Employment')] + self::employmentTypes(),
                'status' => ['_name' => __('Status')] + self::statuses(),
            ]),
        ]);
    }
}
