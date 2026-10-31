<?php

namespace App\Livewire\Timetable;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Section timetables of a term.
 */
#[Layout('layouts::app')]
#[Title('Timetables')]
class Index extends Component
{
    use InteractsWithApi;

    #[Url(as: 'term', except: '')]
    public string $term = '';

    /** @var array<string, string> */
    public array $form = ['section_id' => '', 'slot_template_id' => ''];

    /** @return list<array<string, mixed>> */
    private function templates(): array
    {
        return array_values((array) $this->api()->get('slot-templates', memoize: true)->data());
    }

    public function openCreate(): void
    {
        $this->form = ['section_id' => '', 'slot_template_id' => (string) (collect($this->templates())->firstWhere('is_default', true)['id'] ?? '')];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'timetable-form');
    }

    public function create(): void
    {
        $this->validate(['form.section_id' => 'required|string', 'form.slot_template_id' => 'required|string']);
        $termId = $this->term !== '' ? $this->term : (string) array_key_first(app(Lookups::class)->terms());

        $response = $this->callApi(fn () => $this->api()->post('timetables', $this->form + ['term_id' => $termId]), fieldMap: ['section_id' => 'form.section_id', 'slot_template_id' => 'form.slot_template_id']);

        if ($response !== null) {
            $this->dispatch('close-dialog', 'timetable-form');
            $this->redirectRoute('timetables.show', $response->data('id'), navigate: true);
        }
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);
        $timetables = $this->api()->endpoint('timetables')->all(array_filter(['term' => $this->term]), memoize: false);
        $taken = collect($timetables)->pluck('section.id')->all();

        return view('livewire.timetable.index', [
            'timetables' => $timetables,
            'terms' => $lookups->terms(),
            'templates' => collect($this->templates())->mapWithKeys(fn (array $t) => [$t['id'] => $t['name']])->all(),
            'sections' => collect($this->api()->endpoint('batches')->all(['status' => 'active']))
                ->flatMap(fn (array $batch) => collect($batch['sections'] ?? [])->mapWithKeys(fn (array $s) => [$s['id'] => $batch['name'].' · '.$s['name']]))
                ->except($taken)->all(),
            'canManage' => $this->can('timetable.manage'),
        ]);
    }
}
