<?php

namespace App\Livewire\Academics;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * One class (course × section): syllabus progress and materials. Teachers edit; students read.
 */
#[Layout('layouts::app')]
class ClassShow extends Component
{
    use InteractsWithApi, WithFileUploads;

    #[Locked]
    public string $classId = '';

    /** @var array<string, mixed> */
    public array $material = ['title' => '', 'unit_id' => '', 'url' => '', 'is_published' => true];

    /** @var TemporaryUploadedFile|null */
    public $materialFile = null;

    public function mount(string $class): void
    {
        $this->classId = $class;
    }

    private function path(string $action = ''): string
    {
        return 'offering-sections/'.$this->classId.($action !== '' ? '/'.$action : '');
    }

    public function toggleTopic(string $topicId, bool $covered): void
    {
        $this->callApi(
            fn () => $covered
                ? $this->api()->post($this->path('coverage'), ['topic_id' => $topicId])
                : $this->api()->delete($this->path('coverage/'.$topicId)),
        );
    }

    public function openMaterial(): void
    {
        $this->material = ['title' => '', 'unit_id' => '', 'url' => '', 'is_published' => true];
        $this->materialFile = null;
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'material-form');
    }

    public function addMaterial(): void
    {
        $this->validate([
            'material.title' => 'required|string|max:200',
            'material.url' => 'nullable|required_without:materialFile|url',
            'materialFile' => 'nullable|required_without:material.url|file|max:20480',
        ]);

        $this->callApi(function () {
            $data = [
                'title' => $this->material['title'],
                'unit_id' => $this->material['unit_id'] ?: null,
                'url' => $this->material['url'] ?: null,
                'is_published' => (bool) $this->material['is_published'],
            ];

            if ($this->materialFile !== null) {
                $data['file_id'] = $this->api()->upload($this->materialFile, 'document')['id'];
            }

            return $this->api()->post($this->path('materials'), $data);
        }, fieldMap: ['title' => 'material.title', 'url' => 'material.url', 'file_id' => 'materialFile'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'material-form');
            $this->toast('success', __('Material shared'));
        });
    }

    public function removeMaterial(string $materialId): void
    {
        $this->callApi(fn () => $this->api()->delete($this->path('materials/'.$materialId)), onSuccess: fn () => $this->toast('success', __('Material removed')));
    }

    public function render(): View
    {
        $class = (array) $this->api()->get($this->path())->data();

        return view('livewire.academics.class-show', [
            'class' => $class,
            'units' => collect($class['units'])->mapWithKeys(fn (array $unit) => [$unit['id'] => __('Unit :n: :title', ['n' => $unit['number'], 'title' => $unit['title']])])->all(),
        ])->title($class['course']['code'].' '.$class['section']['name']);
    }
}
