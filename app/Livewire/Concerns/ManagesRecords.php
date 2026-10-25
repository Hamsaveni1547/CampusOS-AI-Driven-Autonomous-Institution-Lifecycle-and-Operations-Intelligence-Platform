<?php

namespace App\Livewire\Concerns;

/**
 * Add / edit / delete in a drawer for simple list screens.
 *
 * The component declares:
 *   - recordsPath(): API path, e.g. 'departments'
 *   - emptyForm(): form defaults
 *   - toForm(array $record): API record → form
 *   - toPayload(): form → API body
 *   - formRules(): Livewire validation rules for form.*
 * and optionally recordLabel() for toasts. Views use the dialogs 'record-form' and 'delete-record'.
 */
trait ManagesRecords
{
    /** @var array<string, mixed> */
    public array $form = [];

    public ?string $editingId = null;

    public ?string $deleteId = null;

    abstract protected function recordsPath(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function emptyForm(): array;

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    abstract protected function toForm(array $record): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function toPayload(): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function formRules(): array;

    protected function recordLabel(): string
    {
        return __('Record');
    }

    /**
     * Load one record for editing; override when the API has no show route.
     *
     * @return array<string, mixed>
     */
    protected function findRecord(string $id): array
    {
        return $this->api()->endpoint($this->recordsPath())->find($id);
    }

    public function mountManagesRecords(): void
    {
        $this->form = $this->emptyForm();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->form = $this->emptyForm();
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'record-form');
    }

    public function openEdit(string $id): void
    {
        $this->editingId = $id;
        $this->form = array_merge($this->emptyForm(), $this->toForm($this->findRecord($id)));
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'record-form');
    }

    public function save(): void
    {
        $this->validate($this->formRules());

        $endpoint = $this->api()->endpoint($this->recordsPath());
        $payload = $this->toPayload();

        $this->callApi(
            fn () => $this->editingId ? $endpoint->update($this->editingId, $payload) : $endpoint->create($payload),
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'record-form');
                $this->toast('success', $this->editingId ? __(':item updated', ['item' => $this->recordLabel()]) : __(':item added', ['item' => $this->recordLabel()]));
                $this->editingId = null;
            },
        );
    }

    public function askDelete(string $id): void
    {
        $this->deleteId = $id;
        $this->dispatch('open-dialog', 'delete-record');
    }

    public function delete(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->callApi(fn () => $this->api()->endpoint($this->recordsPath())->delete($this->deleteId), onSuccess: function (): void {
            $this->deleteId = null;
            $this->dispatch('close-dialog', 'delete-record');
            $this->toast('success', __(':item deleted', ['item' => $this->recordLabel()]));
        });
    }

    /**
     * Empty strings become null; numeric fields are cast by the caller.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function nullBlanks(array $data): array
    {
        return array_map(fn ($value) => $value === '' ? null : $value, $data);
    }
}
