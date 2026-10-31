<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Students below the attendance threshold in a section, with condonation.
 */
#[Layout('layouts::app')]
#[Title('Attendance shortage')]
class Shortage extends Component
{
    use InteractsWithApi;

    #[Url(as: 'section', except: '')]
    public string $section = '';

    #[Url(as: 'term', except: '')]
    public string $term = '';

    #[Url(as: 'standing', except: 'shortage,condonable')]
    public string $standing = 'shortage,condonable';

    /** @var array<string, string> */
    public array $condone = ['student_id' => '', 'class_id' => '', 'name' => '', 'reason' => '', 'fee_reference' => ''];

    public function openCondone(string $studentId, string $classId, string $name): void
    {
        $this->condone = ['student_id' => $studentId, 'class_id' => $classId, 'name' => $name, 'reason' => '', 'fee_reference' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'condone-form');
    }

    public function saveCondone(): void
    {
        $this->validate(['condone.reason' => 'required|string|max:500', 'condone.fee_reference' => 'nullable|string|max:100']);

        $this->callApi(fn () => $this->api()->post('attendance/condonations', [
            'student_id' => $this->condone['student_id'],
            'class_id' => $this->condone['class_id'],
            'reason' => $this->condone['reason'],
            'fee_reference' => $this->condone['fee_reference'] ?: null,
        ]), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'condone-form');
            $this->toast('success', __('Shortage condoned'));
        });
    }

    public function render(): View
    {
        $lookups = app(Lookups::class);

        return view('livewire.attendance.shortage', [
            'sections' => collect($this->api()->endpoint('batches')->all(['status' => 'active']))->flatMap(fn (array $b) => collect($b['sections'] ?? [])->mapWithKeys(fn (array $s) => [$s['id'] => $b['name'].' · '.$s['name']]))->all(),
            'terms' => $lookups->terms(),
            'rows' => $this->section !== '' ? (array) $this->api()->get('attendance/shortage', ['filter' => array_filter(['section' => $this->section, 'term' => $this->term, 'standing' => $this->standing])])->data('rows', []) : [],
            'canCondone' => $this->can('attendance.condonation.manage'),
        ]);
    }
}
