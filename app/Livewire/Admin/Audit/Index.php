<?php

namespace App\Livewire\Admin\Audit;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Who changed what (activity trail) and sign-in/security events.
 */
#[Layout('layouts::app')]
#[Title('Audit log')]
class Index extends Component
{
    use InteractsWithApi;

    #[Url(as: 'tab', except: 'activities')]
    public string $tab = 'activities';

    /** @var array<string, string> */
    #[Url(as: 'f')]
    public array $filters = ['log' => '', 'event' => '', 'severity' => '', 'from' => '', 'to' => ''];

    public ?string $cursor = null;

    /** @var list<string|null> */
    public array $previousCursors = [];

    public function updatedTab(): void
    {
        $this->reset('cursor', 'previousCursors', 'filters');
    }

    public function updatedFilters(): void
    {
        $this->reset('cursor', 'previousCursors');
    }

    public function resetFilters(): void
    {
        $this->reset('cursor', 'previousCursors', 'filters');
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

    /**
     * Filters for the active tab, with dates widened to whole days.
     *
     * @return array<string, string>
     */
    private function apiFilters(): array
    {
        $keys = $this->tab === 'activities' ? ['log', 'event', 'from', 'to'] : ['event', 'severity', 'from', 'to'];
        $filters = array_filter(array_intersect_key($this->filters, array_flip($keys)));

        if (isset($filters['from'])) {
            $filters['from'] .= ' 00:00:00';
        }

        if (isset($filters['to'])) {
            $filters['to'] .= ' 23:59:59';
        }

        return $filters;
    }

    public function render(): View
    {
        $canActivities = $this->can('admin.audit.view');
        $canSecurity = $this->can('identity.security_events.view');

        if ($this->tab === 'activities' && ! $canActivities) {
            $this->tab = 'security';
        }

        $path = $this->tab === 'activities' ? 'audit/activities' : 'audit/security-events';
        $allowed = $this->tab === 'activities' ? $canActivities : $canSecurity;
        $filters = $this->apiFilters();

        return view('livewire.admin.audit.index', [
            'page' => $allowed ? $this->api()->get($path, ['filter' => $filters, 'cursor' => $this->cursor])->page() : null,
            'canActivities' => $canActivities,
            'canSecurity' => $canSecurity,
            'exportUrl' => $this->tab === 'activities' && $this->can('admin.audit.export') ? route('audit.export', ['filter' => $filters]) : null,
        ]);
    }
}
