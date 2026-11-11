<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `@js(...)` is not compiled inside component tags (<x-...>): the browser would get the literal text and the
 * click handler breaks silently. Use {{ \Illuminate\Support\Js::from(...) }} there instead.
 */
class BladeComponentAttributesTest extends TestCase
{
    public function test_no_js_directive_inside_component_tags(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all('/<x-[a-z0-9.:-]+(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $file->getContents(), $tags);

            foreach ($tags[0] as $tag) {
                if (str_contains($tag, '@js(')) {
                    $offenders[] = $file->getRelativePathname();
                }
            }
        }

        $this->assertSame([], array_values(array_unique($offenders)), 'Use {{ \Illuminate\Support\Js::from(...) }} instead of @js inside component tags.');
    }

    public function test_js_from_renders_inside_a_component_attribute(): void
    {
        $html = Blade::render('<x-ui.button wire:click="open({{ \Illuminate\Support\Js::from($name) }})">Go</x-ui.button>', ['name' => "Asha O'Neil"]);

        $this->assertStringContainsString("wire:click=\"open('Asha O\\u0027Neil')\"", $html);
    }
}
