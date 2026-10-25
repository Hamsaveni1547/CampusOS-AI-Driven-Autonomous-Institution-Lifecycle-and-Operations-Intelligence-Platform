<?php

namespace App\Livewire\Academic\AcademicYears;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Academic years with their terms, and the institution calendar (holidays, exams, events).
 */
#[Layout('layouts::app')]
#[Title('Academic years')]
class Index extends Component
{
    use InteractsWithApi;

    #[Url(as: 'tab', except: 'years')]
    public string $tab = 'years';

    #[Url(as: 'month', except: '')]
    public string $month = '';

    public ?string $yearId = null;

    /** @var array<string, string> */
    public array $yearForm = ['name' => '', 'starts_on' => '', 'ends_on' => ''];

    public ?string $termId = null;

    /** @var array<string, string> */
    public array $termForm = ['academic_year_id' => '', 'type' => 'odd', 'name' => '', 'starts_on' => '', 'ends_on' => ''];

    public ?string $eventId = null;

    /** @var array<string, mixed> */
    public array $eventForm = ['title' => '', 'description' => '', 'type' => 'holiday', 'starts_on' => '', 'ends_on' => '', 'is_holiday' => true, 'audience' => 'all'];

    /** @var array{type: string, id: string, action: string}|null */
    public ?array $pending = null;

    /** @return array<string, string> */
    public static function termTypes(): array
    {
        return ['odd' => __('Odd semester'), 'even' => __('Even semester'), 'annual' => __('Annual'), 'summer' => __('Summer term')];
    }

    /** @return array<string, string> */
    public static function eventTypes(): array
    {
        return ['holiday' => __('Holiday'), 'term_start' => __('Term starts'), 'term_end' => __('Term ends'), 'examination' => __('Examination'), 'deadline' => __('Deadline'), 'event' => __('Event'), 'other' => __('Other')];
    }

    public function editYear(?string $id = null): void
    {
        $this->yearId = $id;
        $this->yearForm = ['name' => '', 'starts_on' => '', 'ends_on' => ''];

        if ($id !== null) {
            $year = $this->api()->endpoint('academic-years')->find($id);
            $this->yearForm = ['name' => $year['name'], 'starts_on' => $year['starts_on'], 'ends_on' => $year['ends_on']];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'year-form');
    }

    public function saveYear(): void
    {
        $this->validate(['yearForm.name' => 'required|string|max:20', 'yearForm.starts_on' => 'required|date', 'yearForm.ends_on' => 'required|date|after:yearForm.starts_on']);
        $years = $this->api()->endpoint('academic-years');

        $this->callApi(fn () => $this->yearId ? $years->update($this->yearId, $this->yearForm) : $years->create($this->yearForm), fieldMap: $this->prefixed('yearForm', $this->yearForm), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'year-form');
            $this->toast('success', __('Academic year saved'));
        });
    }

    public function editTerm(string $yearId, ?string $termId = null): void
    {
        $this->termId = $termId;
        $this->termForm = ['academic_year_id' => $yearId, 'type' => 'odd', 'name' => '', 'starts_on' => '', 'ends_on' => ''];

        if ($termId !== null) {
            $term = collect($this->api()->endpoint('academic-years')->find($yearId)['terms'] ?? [])->firstWhere('id', $termId);
            $this->termForm = ['academic_year_id' => $yearId, 'type' => $term['type'], 'name' => $term['name'], 'starts_on' => $term['starts_on'], 'ends_on' => $term['ends_on']];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'term-form');
    }

    public function saveTerm(): void
    {
        $this->validate(['termForm.name' => 'required|string|max:50', 'termForm.starts_on' => 'required|date', 'termForm.ends_on' => 'required|date|after:termForm.starts_on']);
        $data = $this->termId ? array_diff_key($this->termForm, array_flip(['academic_year_id', 'type'])) : $this->termForm;

        $this->callApi(fn () => $this->termId ? $this->api()->patch('terms/'.$this->termId, $data) : $this->api()->post('terms', $data), fieldMap: $this->prefixed('termForm', $this->termForm), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'term-form');
            $this->toast('success', __('Term saved'));
        });
    }

    /**
     * Ask before make-current, close or delete of a year or term.
     */
    public function confirm(string $type, string $id, string $action): void
    {
        $this->pending = ['type' => $type === 'term' ? 'term' : 'year', 'id' => $id, 'action' => in_array($action, ['make-current', 'close', 'delete'], true) ? $action : 'close'];
        $this->dispatch('open-dialog', 'period-action');
    }

    public function runPending(): void
    {
        if ($this->pending === null) {
            return;
        }

        $path = ($this->pending['type'] === 'term' ? 'terms/' : 'academic-years/').$this->pending['id'];

        $this->callApi(fn () => $this->pending['action'] === 'delete' ? $this->api()->delete($path) : $this->api()->post($path.'/'.$this->pending['action']), onSuccess: function (): void {
            $this->pending = null;
            $this->dispatch('close-dialog', 'period-action');
            $this->toast('success', __('Done'));
        });
    }

    public function editEvent(?string $id = null, ?string $date = null): void
    {
        $this->eventId = $id;
        $this->eventForm = ['title' => '', 'description' => '', 'type' => 'holiday', 'starts_on' => $date ?? '', 'ends_on' => '', 'is_holiday' => true, 'audience' => 'all'];

        if ($id !== null) {
            $event = collect($this->events())->firstWhere('id', $id);
            $this->eventForm = ['title' => $event['title'], 'description' => (string) $event['description'], 'type' => $event['type'], 'starts_on' => $event['starts_on'], 'ends_on' => $event['ends_on'], 'is_holiday' => (bool) $event['is_holiday'], 'audience' => $event['audience']];
        }

        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'event-form');
    }

    public function saveEvent(): void
    {
        $this->validate(['eventForm.title' => 'required|string|max:200', 'eventForm.starts_on' => 'required|date', 'eventForm.ends_on' => 'nullable|date|after_or_equal:eventForm.starts_on']);
        $data = $this->eventForm;
        $data['ends_on'] = $data['ends_on'] ?: null;
        $data['description'] = $data['description'] ?: null;
        $events = $this->api()->endpoint('calendar-events');

        $this->callApi(fn () => $this->eventId ? $events->update($this->eventId, $data) : $events->create($data), fieldMap: $this->prefixed('eventForm', $data), onSuccess: function (): void {
            $this->dispatch('close-dialog', 'event-form');
            $this->toast('success', __('Calendar updated'));
        });
    }

    public function deleteEvent(): void
    {
        $this->callApi(fn () => $this->api()->endpoint('calendar-events')->delete((string) $this->eventId), onSuccess: function (): void {
            $this->eventId = null;
            $this->dispatch('close-dialog', 'event-form');
            $this->toast('success', __('Event removed'));
        });
    }

    public function shiftMonth(int $by): void
    {
        $this->month = $this->monthStart()->addMonths($by)->format('Y-m');
    }

    private function monthStart(): Carbon
    {
        return preg_match('/^\d{4}-\d{2}$/', $this->month) ? Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay() : now()->startOfMonth();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(): array
    {
        $start = $this->monthStart();

        return array_values((array) $this->api()->get('calendar-events', ['filter' => ['from' => $start->toDateString(), 'to' => $start->copy()->endOfMonth()->toDateString()]])->data());
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, string>
     */
    private function prefixed(string $prefix, array $form): array
    {
        return collect(array_keys($form))->mapWithKeys(fn ($key) => [$key => $prefix.'.'.$key])->all();
    }

    public function render(): View
    {
        return view('livewire.academic.academic-years.index', [
            'years' => $this->tab === 'years' ? $this->api()->endpoint('academic-years')->all(memoize: false) : [],
            'events' => $this->tab === 'events' ? $this->events() : [],
            'monthStart' => $this->monthStart(),
            'termTypes' => self::termTypes(),
            'eventTypes' => self::eventTypes(),
            'canManage' => $this->can('institution.calendar.manage'),
        ]);
    }
}
