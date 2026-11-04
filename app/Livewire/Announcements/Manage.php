<?php

namespace App\Livewire\Announcements;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Drafting, publishing and withdrawing announcements.
 */
#[Layout('layouts::app')]
#[Title('Post announcements')]
class Manage extends Component
{
    use InteractsWithApi;
    use WithFileUploads;

    /** @var array<int, TemporaryUploadedFile> files to attach on save */
    public array $files = [];

    #[Url(except: '')]
    public string $status = '';

    public string $editingId = '';

    /** @var array<string, mixed> */
    public array $form = [];

    /** Batch whose sections are listed when the audience is "sections". */
    public string $sectionBatch = '';

    public function mount(): void
    {
        $this->form = $this->blank();
    }

    /**
     * @return array<string, mixed>
     */
    private function blank(): array
    {
        return ['title' => '', 'body' => '', 'category' => 'general', 'priority' => 'normal', 'audience_type' => '', 'audience_ids' => [], 'is_pinned' => false, 'requires_ack' => false, 'expires_on' => '', 'publish_at' => ''];
    }

    public function updatedFormAudienceType(): void
    {
        $this->form['audience_ids'] = [];
    }

    /**
     * @param  array<string, mixed>|null  $announcement
     */
    public function open(?array $announcement = null): void
    {
        $this->editingId = $announcement['id'] ?? '';
        $this->form = $announcement === null ? $this->blank() : [
            'title' => $announcement['title'], 'body' => $announcement['body'], 'category' => $announcement['category'], 'priority' => $announcement['priority'],
            'audience_type' => $announcement['audience_type'], 'audience_ids' => $announcement['audience_ids'], 'is_pinned' => $announcement['is_pinned'],
            'requires_ack' => $announcement['requires_ack'], 'expires_on' => $announcement['expires_on'] ?? '',
            'publish_at' => isset($announcement['publish_at']) ? substr((string) $announcement['publish_at'], 0, 16) : '',
        ];
        $this->files = [];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'announcement-form');
    }

    public function save(): void
    {
        $this->validate(['form.title' => 'required|string|max:200', 'form.body' => 'required|string', 'form.audience_type' => 'required|string', 'files' => 'array|max:5', 'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,pdf,doc,docx']);
        $data = [...$this->form, 'attachment_ids' => array_values(array_map(fn (TemporaryUploadedFile $f): string => (string) $this->api()->upload($f, 'attachment')['id'], $this->files)), 'audience_ids' => array_values($this->form['audience_ids']), 'is_pinned' => (bool) $this->form['is_pinned'], 'requires_ack' => (bool) $this->form['requires_ack'], 'expires_on' => $this->form['expires_on'] ?: null, 'publish_at' => $this->form['publish_at'] ?: null];
        $fieldMap = collect(['title', 'body', 'category', 'priority', 'audience_type', 'audience_ids', 'expires_on', 'publish_at'])->mapWithKeys(fn (string $f) => [$f => 'form.'.$f])->all();

        $this->callApi(fn () => $this->editingId === '' ? $this->api()->post('announcements', $data) : $this->api()->put('announcements/'.$this->editingId, $data), fieldMap: $fieldMap, onSuccess: function (): void {
            $this->files = [];
            $this->dispatch('close-dialog', 'announcement-form');
            $this->toast('success', __('Draft saved. Publish it when ready.'));
        });
    }

    public function publish(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('announcements/'.$id.'/publish'), onSuccess: fn ($response) => $this->toast('success', (string) $response->data('message')));
    }

    public function withdraw(string $id): void
    {
        $this->callApi(fn () => $this->api()->post('announcements/'.$id.'/withdraw'), onSuccess: fn () => $this->toast('success', __('Announcement withdrawn')));
    }

    public function delete(string $id): void
    {
        $this->callApi(fn () => $this->api()->delete('announcements/'.$id), onSuccess: fn () => $this->toast('success', __('Draft deleted')));
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $options = (array) $this->api()->get('announcements/audiences', memoize: true)->data();
        $type = $this->form['audience_type'];

        $limits = $options['limits'] ?? ['departments' => null, 'sections' => null];
        $targets = match ($type) {
            'roles' => collect($options['roles'])->mapWithKeys(fn (array $r) => [$r['value'] => $r['label']])->all(),
            'departments' => $lookups->departments(),
            'programmes' => $lookups->programmes(),
            'batches' => $lookups->batches(),
            'sections' => $this->sectionBatch === '' ? [] : collect($lookups->sections($this->sectionBatch))->all(),
            default => [],
        };

        // Only offer what this publisher may target (a HOD's department, a teacher's own sections).
        $allowed = match ($type) {
            'departments' => $limits['departments'],
            'sections' => $limits['sections'],
            default => null,
        };
        if ($allowed !== null) {
            $targets = array_intersect_key($targets, array_flip($allowed));
        }

        return view('livewire.announcements.manage', [
            'items' => (array) $this->api()->withoutCache(fn () => $this->api()->get('announcements', ['filter' => array_filter(['status' => $this->status])]))->data(),
            'types' => collect($options['types'])->mapWithKeys(fn (string $t) => [$t => __(ucfirst($t))])->all(),
            'categories' => collect($options['categories'])->mapWithKeys(fn (array $c) => [$c['value'] => $c['label']])->all(),
            'targets' => $targets,
            'batches' => $type === 'sections' ? $lookups->batches() : [],
        ]);
    }
}
