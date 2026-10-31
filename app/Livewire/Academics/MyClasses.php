<?php

namespace App\Livewire\Academics;

use App\Livewire\Concerns\InteractsWithApi;
use App\Support\Lookups;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "My classes": what a teacher teaches this term, or a student's courses this term.
 */
#[Layout('layouts::app')]
#[Title('My classes')]
class MyClasses extends Component
{
    use InteractsWithApi;

    public function render(): View
    {
        $isTeacher = $this->user()?->hasAnyRole(['faculty', 'hod']) ?? false;
        $isStudent = $this->user()?->hasAnyRole(['student']) ?? false;
        $calls = array_filter(['teaching' => $isTeacher ? 'me/teaching' : null, 'courses' => $isStudent ? 'me/courses' : null]);
        $data = $calls !== [] ? $this->api()->getMany($calls) : [];

        return view('livewire.academics.my-classes', [
            'teaching' => isset($data['teaching']) ? (array) $data['teaching']->data() : null,
            'courses' => isset($data['courses']) ? (array) $data['courses']->data() : null,
            'components' => app(Lookups::class)->academicsOptions('components'),
        ]);
    }
}
