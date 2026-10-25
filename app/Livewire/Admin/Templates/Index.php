<?php

namespace App\Livewire\Admin\Templates;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Email templates the institution can customise, and the delivery log.
 */
#[Layout('layouts::app')]
#[Title('Notification templates')]
class Index extends Component
{
    use InteractsWithApi;

    #[Url(as: 'tab', except: 'templates')]
    public string $tab = 'templates';

    public ?string $editingKey = null;

    public string $editingChannel = 'mail';

    /** @var array<string, mixed> */
    public array $form = ['subject' => '', 'body' => '', 'is_active' => true];

    /** @var list<string> */
    public array $placeholders = [];

    public bool $isCustom = false;

    /** @var array<string, string> */
    public array $deliveryFilters = ['status' => '', 'channel' => ''];

    public ?string $cursor = null;

    /** @var list<string|null> */
    public array $previousCursors = [];

    public function edit(string $key, string $channel): void
    {
        $template = $this->api()->get('notification-templates/'.$key)->data();
        $entry = collect($template['channels'])->firstWhere('channel', $channel);

        $this->editingKey = $key;
        $this->editingChannel = $channel;
        $this->placeholders = $template['placeholders'];
        $this->isCustom = $entry['custom'] !== null;
        $source = $entry['custom'] ?? $entry['default'];
        $this->form = ['subject' => (string) $source['subject'], 'body' => (string) $source['body'], 'is_active' => (bool) ($entry['custom']['is_active'] ?? true)];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'template-form');
    }

    public function save(): void
    {
        $this->validate(['form.subject' => 'required|string|max:255', 'form.body' => 'required|string|max:10000']);

        $this->callApi(
            fn () => $this->api()->put('notification-templates/'.$this->editingKey.'/'.$this->editingChannel, $this->form),
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'template-form');
                $this->toast('success', __('Template saved'));
            },
        );
    }

    public function resetTemplate(): void
    {
        $this->callApi(
            fn () => $this->api()->delete('notification-templates/'.$this->editingKey.'/'.$this->editingChannel),
            onSuccess: function (): void {
                $this->dispatch('close-dialog', 'template-form');
                $this->toast('success', __('Back to the built-in template'));
            },
        );
    }

    public function updatedDeliveryFilters(): void
    {
        $this->reset('cursor', 'previousCursors');
    }

    public function nextPage(string $cursor): void
    {
        $this->previousCursors[] = $this->cursor;
        $this->cursor = $cursor;
    }

    public function previousPage(): void
    {
        $this->cursor = array_pop($this->previousCursors);
    }

    public function render(): View
    {
        $canDeliveries = $this->can('communication.deliveries.view');
        $canTemplates = $this->can('communication.templates.manage');

        if (! $canTemplates && $canDeliveries) {
            $this->tab = 'deliveries';
        }

        return view('livewire.admin.templates.index', [
            'templates' => $canTemplates && $this->tab === 'templates' ? (array) $this->api()->get('notification-templates')->data() : [],
            'deliveries' => $canDeliveries && $this->tab === 'deliveries'
                ? $this->api()->get('notification-deliveries', ['filter' => array_filter($this->deliveryFilters), 'cursor' => $this->cursor])->page()
                : null,
            'canDeliveries' => $canDeliveries,
        ]);
    }
}
