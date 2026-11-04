<?php

namespace Tests\Feature\Announcements;

use App\Livewire\Announcements\Feed;
use App\Livewire\Announcements\Manage;
use App\Livewire\Announcements\Show;
use App\Livewire\Home;
use App\Livewire\Reports\Index;
use App\Livewire\Reports\Show as ReportShow;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementAndReportScreensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    /**
     * @return array<string, mixed>
     */
    private function announcement(array $extra = []): array
    {
        return ['id' => 'a1', 'title' => 'Lab moved to Friday', 'body' => 'The DBMS lab moves to Friday.', 'category' => 'academic', 'priority' => 'important', 'is_pinned' => false, 'requires_ack' => true, 'expires_on' => null, 'published_at' => '2026-11-04T10:00:00+05:30', 'publish_at' => null, 'from' => 'Kavya Shetty', ...$extra];
    }

    public function test_reader_sees_feed_and_acknowledges(): void
    {
        $this->signIn(['roles' => ['student']]);
        Http::fake([
            self::API.'me/announcements*' => Http::response(['data' => [$this->announcement(['read_at' => null, 'acknowledged_at' => null])], 'meta' => ['total' => 1]]),
            self::API.'announcements/a1/acknowledge' => Http::response(['message' => 'Acknowledged.']),
            self::API.'announcements/a1' => Http::response(['data' => $this->announcement(['read_at' => '2026-11-04T11:00:00+05:30', 'acknowledged_at' => null, 'can_manage' => false])]),
        ]);

        Livewire::test(Feed::class)->assertSee('Lab moved to Friday')->assertSee('Please acknowledge');
        Livewire::test(Show::class, ['announcement' => 'a1'])->assertSee('I have read and understood this')->call('acknowledge')->assertHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'a1/acknowledge'));
    }

    public function test_publisher_drafts_and_publishes(): void
    {
        $this->signIn(['permissions' => ['communication.announcements.publish'], 'roles' => ['faculty']]);
        Http::fake([
            self::API.'announcements/audiences' => Http::response(['data' => ['types' => ['departments', 'programmes', 'batches', 'sections'], 'roles' => [], 'categories' => [['value' => 'academic', 'label' => 'Academics'], ['value' => 'general', 'label' => 'General announcements']]]]),
            self::API.'batches*' => Http::response(['data' => [['id' => 'b1', 'name' => 'BE CSE 2024']]]),
            self::API.'sections*' => Http::response(['data' => [['id' => 'sec1', 'name' => 'A']]]),
            self::API.'announcements/a1/publish' => Http::response(['data' => ['message' => 'Sent to 60 people.', 'recipients' => 60]]),
            self::API.'announcements*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => ['id' => 'a1']], 201)
                : Http::response(['data' => [$this->announcement(['status' => 'draft', 'audience_type' => 'sections', 'audience' => 'BE CSE 2024 A', 'audience_ids' => ['sec1'], 'recipients_count' => 0, 'read_count' => 0, 'ack_count' => 0])], 'meta' => ['total' => 1]]),
        ]);

        Livewire::test(Manage::class)
            ->assertSee('BE CSE 2024 A')
            ->call('open')
            ->set('form.title', 'Internal test on Monday')
            ->set('form.body', 'Units 1 and 2.')
            ->set('form.audience_type', 'sections')
            ->set('sectionBatch', 'b1')
            ->assertSee('A')
            ->set('form.audience_ids', ['sec1'])
            ->call('save')
            ->assertHasNoErrors()
            ->call('publish', 'a1');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), 'announcements') && $r['audience_ids'] === ['sec1'] && $r['requires_ack'] === false);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'a1/publish'));
    }

    public function test_reports_run_and_download_and_home_cards(): void
    {
        $this->signIn(['permissions' => ['finance.reports.view'], 'roles' => ['finance_officer']]);
        $report = ['key' => 'fee-collection', 'label' => 'Fee collection', 'module' => 'Finance', 'description' => 'Receipts between two dates.', 'filters' => ['from', 'to'],
            'columns' => [['key' => 'receipt_no', 'label' => 'Receipt', 'type' => 'text'], ['key' => 'amount', 'label' => 'Amount', 'type' => 'money']]];
        $this->fakeShell([
            self::API.'reports/fee-collection*' => Http::response(['data' => [...$report, 'rows' => [['receipt_no' => 'R-0001', 'amount' => 45000]], 'applied_filters' => [], 'generated_at' => '2026-11-04T10:00:00+05:30', 'generated_by' => 'Accounts']]),
            self::API.'reports' => Http::response(['data' => [$report]]),
            self::API.'me/dashboard' => Http::response(['data' => ['cards' => [['key' => 'collected', 'label' => 'Collected this month', 'value' => 125000, 'format' => 'money', 'tone' => 'success', 'hint' => null, 'link' => 'reports']]]]),
        ]);

        Livewire::test(Index::class)->assertSee('Fee collection');
        Livewire::test(ReportShow::class, ['key' => 'fee-collection'])->assertSee('R-0001')->assertSee('₹45,000.00')->call('download')->assertFileDownloaded('fee-collection-'.now()->format('Y-m-d').'.csv');
        Livewire::test(Home::class)->assertSee('Collected this month')->assertSee('₹125,000', false);
    }
}
