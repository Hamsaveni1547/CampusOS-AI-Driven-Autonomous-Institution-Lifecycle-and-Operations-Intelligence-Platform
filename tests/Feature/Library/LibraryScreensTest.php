<?php

namespace Tests\Feature\Library;

use App\Livewire\Library\Catalogue;
use App\Livewire\Library\Desk;
use App\Livewire\Library\MyLibrary;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class LibraryScreensTest extends TestCase
{
    private const META = ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->signIn(['permissions' => ['library.catalogue.manage', 'library.circulation.manage', 'library.fines.waive', 'library.reports.view'], 'roles' => ['librarian']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loan(bool $overdue = false, string $fine = 'none'): array
    {
        return ['id' => 'l1', 'book' => ['id' => 'b1', 'title' => 'Database System Concepts', 'authors' => 'Silberschatz'], 'accession_no' => 'BIT0001', 'member' => ['id' => 'u1', 'name' => 'Asha', 'type' => 'student'], 'issued_on' => '2026-11-01', 'due_on' => '2026-11-15', 'returned_on' => null, 'renewals' => 0, 'overdue' => $overdue, 'fine' => $fine === 'due' ? 10.0 : 0.0, 'fine_status' => $fine, 'fine_note' => null];
    }

    public function test_catalogue_search_and_add_book_with_copies(): void
    {
        Http::fake([
            self::API.'library/books?*' => Http::response(['data' => [['id' => 'b1', 'title' => 'Database System Concepts', 'authors' => 'Silberschatz', 'isbn' => null, 'publisher' => null, 'edition' => '7th', 'published_year' => 2019, 'subject' => 'Databases', 'call_number' => null, 'copies' => 3, 'available' => 2]], 'meta' => self::META]),
            self::API.'library/books' => Http::response(['data' => ['id' => 'b2']], 201),
        ]);

        Livewire::test(Catalogue::class)
            ->assertSee('Database System Concepts')
            ->assertSeeInOrder(['2', '/ 3'])
            ->call('open')
            ->set('book.title', 'Operating System Concepts')
            ->set('book.authors', 'Silberschatz, Galvin')
            ->set('book.accessions', 'BIT0101, BIT0102')
            ->set('book.shelf', 'R2-S4')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'library/books') && $r['copies'] === [['accession_no' => 'BIT0101', 'shelf' => 'R2-S4'], ['accession_no' => 'BIT0102', 'shelf' => 'R2-S4']]);
    }

    public function test_desk_issues_returns_and_collects_fines(): void
    {
        Http::fake([
            self::API.'library/summary' => Http::response(['data' => ['titles' => 120, 'copies' => 400, 'on_loan' => 35, 'overdue' => 4, 'fines_due' => 40.0]]),
            self::API.'library/loans?*' => Http::response(['data' => [$this->loan(true, 'due')], 'meta' => self::META]),
            self::API.'library/member*' => Http::response(['data' => ['member' => ['id' => 'u1', 'name' => 'Asha', 'email' => 'asha@x', 'type' => 'student'], 'dues' => ['on_loan' => 1, 'overdue' => 0, 'fines' => 0.0, 'no_dues' => false], 'current' => [$this->loan()], 'history' => []]]),
            self::API.'library/issue' => Http::response(['data' => ['id' => 'l2', 'due_on' => '2026-11-18']], 201),
            self::API.'library/return' => Http::response(['data' => ['id' => 'l1', 'fine' => 10.0]]),
            self::API.'library/loans/l1/fine' => Http::response(['message' => 'ok']),
        ]);

        Livewire::test(Desk::class)
            ->assertSee('₹40.00')
            ->set('member', 'BIT25MCA001')
            ->call('lookup')
            ->assertSee('Asha')
            ->set('issueAccession', 'BIT0002')
            ->call('issue')
            ->set('returnAccession', 'BIT0001')
            ->call('returnBook')
            ->call('collect', 'l1');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'library/issue') && $r['accession_no'] === 'BIT0002' && $r['member'] === 'BIT25MCA001');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'library/return') && $r['accession_no'] === 'BIT0001');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'l1/fine') && $r['how'] === 'paid');
    }

    public function test_member_renews_a_loan(): void
    {
        Http::fake([
            self::API.'me/library' => Http::response(['data' => ['dues' => ['on_loan' => 1, 'overdue' => 0, 'fines' => 0.0, 'no_dues' => false], 'current' => [$this->loan()], 'history' => []]]),
            self::API.'library/options' => Http::response(['data' => ['student_limit' => 4, 'staff_limit' => 8, 'student_days' => 14, 'staff_days' => 30, 'max_renewals' => 2, 'fine_per_day' => 2]]),
            self::API.'library/loans/l1/renew' => Http::response(['data' => ['id' => 'l1', 'due_on' => '2026-11-29']]),
        ]);

        Livewire::test(MyLibrary::class)->assertSee('Database System Concepts')->call('renew', 'l1');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'l1/renew'));
    }
}
