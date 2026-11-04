<?php

namespace App\Livewire\Library;

use App\Livewire\Concerns\InteractsWithApi;
use App\Livewire\Concerns\WithTable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Library catalogue: search for everyone; add books and copies for library staff.
 */
#[Layout('layouts::app')]
#[Title('Library')]
class Catalogue extends Component
{
    use InteractsWithApi, WithTable;

    /** @var array<string, mixed> */
    public array $book = [];

    /** @var array<string, mixed>|null */
    public ?array $detail = null;

    /** @var array<string, string> */
    public array $copy = ['accession_no' => '', 'shelf' => ''];

    public function open(): void
    {
        $this->book = ['title' => '', 'authors' => '', 'isbn' => '', 'publisher' => '', 'edition' => '', 'published_year' => '', 'subject' => '', 'call_number' => '', 'accessions' => '', 'shelf' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'book-form');
    }

    public function save(): void
    {
        $this->validate(['book.title' => 'required|string|max:300', 'book.authors' => 'required|string|max:300', 'book.published_year' => 'nullable|integer']);
        $b = $this->book;
        $accessions = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $b['accessions']) ?: [])));
        $data = [
            ...collect($b)->only(['title', 'authors', 'isbn', 'publisher', 'edition', 'subject', 'call_number'])->map(fn ($v) => $v === '' ? null : $v)->all(),
            'published_year' => $b['published_year'] !== '' ? (int) $b['published_year'] : null,
            'copies' => array_map(fn (string $a) => ['accession_no' => $a, 'shelf' => $b['shelf'] ?: null], $accessions),
        ];

        $this->callApi(fn () => $this->api()->post('library/books', $data), fieldMap: ['title' => 'book.title', 'authors' => 'book.authors'], onSuccess: function (): void {
            $this->dispatch('close-dialog', 'book-form');
            $this->toast('success', __('Book added'));
        });
    }

    public function show(string $id): void
    {
        $this->detail = (array) $this->api()->withoutCache(fn () => $this->api()->get('library/books/'.$id))->data();
        $this->copy = ['accession_no' => '', 'shelf' => ''];
        $this->resetErrorBag();
        $this->dispatch('open-dialog', 'book-detail');
    }

    public function addCopy(): void
    {
        $this->validate(['copy.accession_no' => 'required|string|max:30']);
        $id = $this->detail['id'] ?? '';

        $this->callApi(fn () => $this->api()->post('library/books/'.$id.'/copies', ['accession_no' => $this->copy['accession_no'], 'shelf' => $this->copy['shelf'] ?: null]), fieldMap: ['accession_no' => 'copy.accession_no'], onSuccess: function () use ($id): void {
            $this->toast('success', __('Copy added'));
            $this->show($id);
        });
    }

    public function setCopyStatus(string $copyId, string $status): void
    {
        $id = $this->detail['id'] ?? '';
        $this->callApi(fn () => $this->api()->patch('library/copies/'.$copyId, ['status' => $status]), onSuccess: fn () => $this->show($id));
    }

    public function render(): View
    {
        return view('livewire.library.catalogue', ['rows' => $this->api()->endpoint('library/books')->page($this->activeFilters(), $this->tableQuery())]);
    }
}
