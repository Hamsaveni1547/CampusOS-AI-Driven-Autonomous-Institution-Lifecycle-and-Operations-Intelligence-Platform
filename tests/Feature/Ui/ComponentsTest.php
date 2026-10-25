<?php

namespace Tests\Feature\Ui;

use App\Api\Page;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;
use Tests\TestCase;

class ComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_style_guide_renders_every_component(): void
    {
        $this->get('/_ui')
            ->assertOk()
            ->assertSee('CampusOS UI')
            ->assertSee('Deactivate Anita Rao?')
            ->assertSee('RVCEEMP0001');
    }

    public function test_button_variants_link_and_loading(): void
    {
        $html = Blade::render('<x-ui.button href="/x" icon="plus" variant="secondary">Add</x-ui.button>');
        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('href="/x"', $html);
        $this->assertStringContainsString('<svg', $html);

        $html = Blade::render('<x-ui.button wire:click="save" loading="save">Save</x-ui.button>');
        $this->assertStringContainsString('wire:loading.attr="disabled"', $html);
        $this->assertStringContainsString('wire:target="save"', $html);

        $html = Blade::render('<x-ui.button square icon="x" label="Close" />');
        $this->assertStringContainsString('aria-label="Close"', $html);
    }

    public function test_input_shows_label_required_mark_and_error_from_bag(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['form.email' => ['The email is taken.']]));
        view()->share('errors', $errors);

        $html = Blade::render('<x-ui.input label="Email" wire:model="form.email" required />');

        $this->assertStringContainsString('for="f-form-email"', $html);
        $this->assertStringContainsString('id="f-form-email"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('The email is taken.', $html);
        $this->assertStringContainsString('cos-control-invalid', $html);
    }

    public function test_status_badge_uses_configured_tone(): void
    {
        $this->assertStringContainsString('text-success', Blade::render('<x-ui.status-badge status="active" />'));
        $this->assertStringContainsString('Pending verification', Blade::render('<x-ui.status-badge status="pending_verification" />'));
        $this->assertStringContainsString('text-danger', Blade::render('<x-ui.status-badge status="locked" />'));
    }

    public function test_combobox_and_select_render_options(): void
    {
        $html = Blade::render('<x-ui.combobox label="Department" name="dept" :options="[\'cse\' => \'CSE\', \'ece\' => \'ECE\']" />');
        $this->assertStringContainsString('<option value="cse">CSE</option>', $html);
        $this->assertStringContainsString('combobox(', $html);

        $html = Blade::render('<x-ui.select label="Level" name="level" placeholder="Choose" :options="[[\'value\' => \'ug\', \'label\' => \'UG\']]" />');
        $this->assertStringContainsString('<option value="ug">UG</option>', $html);
        $this->assertStringContainsString('<option value="">Choose</option>', $html);
    }

    public function test_pagination_shows_range_and_pages(): void
    {
        $html = Blade::render('<x-ui.pagination :page="$page" />', ['page' => new Page(items: [['id' => 1]], currentPage: 3, lastPage: 12, perPage: 25, total: 289)]);

        $this->assertStringContainsString('51–51 of 289', $html);
        $this->assertStringContainsString('gotoPage(12)', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_icons_are_inline_and_hidden_from_screen_readers(): void
    {
        $decorative = Blade::render('<x-ui.icon name="bell" class="size-5" />');
        $this->assertStringContainsString('aria-hidden="true"', $decorative);
        $this->assertStringContainsString('class="size-5 shrink-0"', $decorative);

        $labelled = Blade::render('<x-ui.icon name="bell" label="Notifications" />');
        $this->assertStringContainsString('aria-label="Notifications"', $labelled);
    }

    public function test_unknown_icon_fails_loudly_in_tests(): void
    {
        $this->expectException(ViewException::class);

        Blade::render('<x-ui.icon name="does-not-exist" />');
    }

    public function test_modal_confirm_and_drawer_wire_to_dialog_events(): void
    {
        $html = Blade::render('<x-ui.confirm-dialog name="del" title="Delete?" action="delete" confirm-text="CSE">Gone.</x-ui.confirm-dialog>');
        $this->assertStringContainsString('open-dialog.window', $html);
        $this->assertStringContainsString('wire:click="delete"', $html);
        $this->assertStringContainsString('typed !== ', $html);

        $html = Blade::render('<x-ui.drawer name="room" title="Add room">Body</x-ui.drawer>');
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('x-trap.noscroll.inert', $html);
    }

    public function test_error_pages_render(): void
    {
        foreach ([403 => "You don't have access", 404 => 'Page not found', 419 => 'Page expired', 429 => 'Too many attempts', 500 => 'Something went wrong', 503 => 'temporarily unavailable'] as $status => $text) {
            $this->assertStringContainsString(e($text), view("errors.{$status}")->render());
        }
    }
}
