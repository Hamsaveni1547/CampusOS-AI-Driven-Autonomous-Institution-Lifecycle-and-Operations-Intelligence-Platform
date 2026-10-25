<?php

namespace App\Livewire\Admin\Imports;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * CSV imports: upload, the API checks every row, then commit or cancel.
 */
#[Layout('layouts::app')]
#[Title('Imports')]
class Index extends Component
{
    use InteractsWithApi, WithFileUploads, WithTable;

    /** Type pre-selected from a link such as ?type=users */
    #[Url(as: 'type', except: '')]
    public string $type = '';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public bool $sendInvites = true;

    public ?string $openId = null;

    /**
     * @return array<string, string>
     */
    private function types(): array
    {
        return array_filter([
            'users' => $this->can('identity.users.import') ? __('Users') : null,
            'employees' => $this->can('hr.employees.import') ? __('Staff') : null,
        ]);
    }

    public function mount(): void
    {
        if ($this->type !== '' && array_key_exists($this->type, $this->types())) {
            $this->dispatch('open-dialog', 'import-start');
        }
    }

    public function openStart(): void
    {
        $this->reset('file');
        $this->type = $this->type ?: (string) array_key_first($this->types());
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'import-start');
    }

    public function start(): void
    {
        $this->validate([
            'type' => ['required', 'in:'.implode(',', array_keys($this->types()))],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $response = $this->callApi(function () {
            $stored = $this->api()->upload($this->file, 'import');

            return $this->api()->post('imports', array_filter([
                'type' => $this->type,
                'file_id' => $stored['id'],
                'options' => ['send_invite' => $this->sendInvites],
            ]));
        }, fieldMap: ['file_id' => 'file']);

        if ($response !== null) {
            $this->file = null;
            $this->dispatch('close-dialog', 'import-start');
            $this->show((string) $response->data('id'));
        }
    }

    public function show(string $importId): void
    {
        $this->openId = $importId;
        $this->dispatch('open-dialog', 'import-detail');
    }

    public function commit(): void
    {
        $this->callApi(fn () => $this->api()->endpoint('imports')->action((string) $this->openId, 'commit'), onSuccess: fn () => $this->toast('success', __('Import started'), __('Rows are being added in the background.')));
    }

    public function cancel(): void
    {
        $this->callApi(fn () => $this->api()->endpoint('imports')->action((string) $this->openId, 'cancel'), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'import-detail');
            $this->toast('success', __('Import cancelled'));
        });
    }

    public function render(): View
    {
        $types = $this->types();
        $open = $this->openId ? $this->api()->endpoint('imports')->find($this->openId) : null;
        $templateType = $open['type'] ?? ($this->type ?: array_key_first($types));

        return view('livewire.admin.imports.index', [
            'page' => $this->api()->endpoint('imports')->page($this->activeFilters(null), $this->tableQuery()),
            'types' => $types,
            'open' => $open,
            'busy' => in_array($open['status'] ?? null, ['pending', 'validating', 'processing'], true),
            'columns' => $templateType ? $this->api()->get('imports/templates/'.$templateType, memoize: true)->data() : null,
        ]);
    }
}
