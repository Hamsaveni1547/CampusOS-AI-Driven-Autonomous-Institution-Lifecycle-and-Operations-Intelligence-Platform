<?php

namespace Tests\Feature\Ui;

use App\Support\Icons;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class IconsTest extends TestCase
{
    public function test_any_lucide_icon_renders(): void
    {
        $svg = Icons::svg('star', 'size-5', 'Favourite');

        $this->assertStringContainsString('role="img" aria-label="Favourite"', $svg);
        $this->assertStringContainsString('class="size-5 shrink-0"', $svg);
        $this->assertStringContainsString('<path', $svg);
    }

    public function test_unknown_icon_fails_loudly_during_development(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Icons::svg('not-a-real-icon-name');
    }

    public function test_every_icon_name_used_in_views_and_navigation_exists(): void
    {
        $sources = collect(File::allFiles(resource_path('views')))
            ->map(fn ($file) => $file->getContents())
            ->push(File::get(config_path('navigation.php')))
            ->implode("\n");

        preg_match_all('/(?<![\w-])(?:icon|icon-right)="([a-z0-9-]+)"|\'icon\' => \'([a-z0-9-]+)\'|Icons::svg\(\'([a-z0-9-]+)\'/', $sources, $matches);

        $names = collect([...$matches[1], ...$matches[2], ...$matches[3]])->filter()->unique()->values();
        $missing = $names->reject(fn ($name) => Icons::exists($name))->values()->all();

        $this->assertGreaterThan(50, $names->count());
        $this->assertSame([], $missing, 'Icons not in Lucide: '.implode(', ', $missing));
    }
}
