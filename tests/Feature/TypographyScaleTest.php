<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The application has a type scale, and nothing may quietly step outside it.
 *
 * The views had accumulated eighty arbitrary pixel sizes across thirty-seven
 * files — seventy of them the same `text-[11px]` — plus a `text-[9px]` axis
 * label on the production report. Two sizes were below a comfortable reading
 * size and the rest had no shared name, so a single adjustment could not be
 * made coherently.
 */
class TypographyScaleTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: int}>
     */
    private function bladeFiles(): array
    {
        $files = [];

        foreach (glob(resource_path('views').'/**/*.blade.php') ?: [] as $path) {
            if (is_file($path)) {
                $files[] = $path;
            }
        }

        // glob() does not recurse reliably, so walk the tree explicitly.
        if ($files !== []) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function test_no_view_declares_an_arbitrary_font_size(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            preg_match_all('/\btext-\[(\d+(?:\.\d+)?)px\]/', file_get_contents($path), $matches);

            foreach ($matches[1] as $size) {
                $offenders[] = sprintf('%s (%spx)', str_replace(resource_path('views').'/', '', $path), $size);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Arbitrary font sizes bypass the scale:\n".implode("\n", array_slice($offenders, 0, 15)),
        );
    }

    public function test_nothing_is_smaller_than_eleven_pixels(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            preg_match_all('/\btext-\[(\d+(?:\.\d+)?)px\]/', file_get_contents($path), $matches);

            foreach ($matches[1] as $size) {
                if ((float) $size < 11) {
                    $offenders[] = sprintf('%s (%spx)', str_replace(resource_path('views').'/', '', $path), $size);
                }
            }
        }

        $this->assertSame([], $offenders, 'Text below 11px is not legible enough to ship.');
    }

    public function test_the_scale_classes_exist_and_are_defined(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        foreach (['.text-label', '.text-meta', '.text-nav'] as $class) {
            $this->assertStringContainsString($class, $css, "The {$class} scale step is not defined.");
        }

        // The label step states case, weight and tracking once, so a view does
        // not repeat them and drift apart.
        $this->assertMatchesRegularExpression(
            '/\.text-label\s*\{[^}]*uppercase[^}]*tracking-wide/s',
            $css,
        );
    }

    public function test_the_scale_is_actually_used(): void
    {
        $used = ['text-label' => 0, 'text-meta' => 0];

        foreach ($this->bladeFiles() as $path) {
            $contents = file_get_contents($path);

            foreach (array_keys($used) as $class) {
                $used[$class] += substr_count($contents, $class);
            }
        }

        $this->assertGreaterThan(5, $used['text-label'], 'The label step is defined but unused.');
        $this->assertGreaterThan(20, $used['text-meta'], 'The meta step is defined but unused.');
    }
}
