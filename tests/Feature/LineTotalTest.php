<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Line totals in the item forms.
 *
 * The arithmetic is JavaScript, so what can be pinned here is the contract the
 * script relies on: which inputs take part, where the figures are written, and
 * that the element carrying the total actually contains the rows it is adding
 * up. That last one is the bug worth guarding — see the scope test below.
 */
class LineTotalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The forms whose lines carry both a quantity and a price.
     *
     * @return list<array{0: string}>
     */
    public static function pricedForms(): array
    {
        return [
            'purchase order' => ['purchase-orders.create'],
            'sales order' => ['sales-orders.create'],
            'goods receipt' => ['goods-receipts.create'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->owner());
    }

    #[DataProvider('pricedForms')]
    public function test_every_priced_line_carries_a_total_column(string $route): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        // Every row the page renders, and the row the template hands to JavaScript
        // when a line is added, must have somewhere to put its figure.
        $rows = substr_count($html, 'data-line-row');
        $amounts = substr_count($html, 'data-line-amount');
        $totals = substr_count($html, 'data-line-total');

        $this->assertGreaterThan(0, $rows, 'No line rows were rendered.');
        $this->assertSame(
            $rows,
            $amounts,
            'Every row needs exactly one quantity input, or the total multiplies nothing.',
        );
        $this->assertSame(
            $rows,
            $totals,
            'Every row needs exactly one total cell, or a line figure has nowhere to go.',
        );
    }

    #[DataProvider('pricedForms')]
    public function test_the_total_column_and_its_heading_are_present(string $route): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString(__('Line total'), $html);
        $this->assertStringContainsString('data-line-grand-total', $html);
    }

    /**
     * The scope marker has to enclose the rows *and* the total.
     *
     * It originally sat on the scrolling table div while the order total was
     * rendered in the footer strip below it. The script looked for the total
     * inside the scope and found nothing, so every page showed a dash where the
     * figure should have been — a filled-in form and an empty one both read
     * "—", and nothing in the build or the suite said so. Only loading the page
     * and looking at it caught it.
     */
    #[DataProvider('pricedForms')]
    public function test_the_scope_encloses_both_the_rows_and_the_total(string $route): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        $marker = strpos($html, 'data-line-editor');

        $this->assertNotFalse($marker, 'The recalculation scope marker is missing.');

        // Back up to the opening tag the marker sits inside. Starting the walk at
        // the marker itself would count the element's *children* as if they were
        // the element, and the depth would close one level too early.
        $tagStart = strrpos(substr($html, 0, $marker), '<div');

        $this->assertNotFalse($tagStart, 'Could not find the tag carrying the scope marker.');

        $position = $tagStart;
        $depth = 0;
        $close = null;

        while (preg_match('#<(/?)div\b[^>]*>#', $html, $match, PREG_OFFSET_CAPTURE, $position)) {
            $position = $match[0][1] + strlen($match[0][0]);

            if ($match[1][0] === '') {
                $depth++;
            } elseif (--$depth === 0) {
                $close = $position;
                break;
            }
        }

        $this->assertNotNull($close, 'Could not find where the scope element ends.');

        $inside = substr($html, $tagStart, $close - $tagStart);

        $this->assertStringContainsString(
            'data-line-row',
            $inside,
            'The scope must enclose the rows it is meant to add up.',
        );
        $this->assertStringContainsString(
            'data-line-grand-total',
            $inside,
            'The scope must enclose the order total, or the figure can never be written.',
        );
    }

    public function test_the_currency_is_read_from_the_form_rather_than_written_in_javascript(): void
    {
        $html = $this->get(route('purchase-orders.create'))->assertOk()->getContent();

        $this->assertStringContainsString('data-line-currency="Rp"', $html);

        // A literal prefix in the script would drift the moment `<x-money>`
        // changed, and the two would disagree on screen.
        $script = $this->code(file_get_contents(resource_path('js/app.js')));

        $this->assertStringContainsString('dataset.lineCurrency', $script);
        $this->assertStringNotContainsString("'Rp'", $script, 'The currency belongs to the form, not to the script.');
    }

    public function test_the_thousands_separator_follows_the_active_locale(): void
    {
        $script = $this->code(file_get_contents(resource_path('js/app.js')));

        // `Number.format` is hard-coded to Indonesian in one place and the
        // document's own language in the other; the locale has to be read, not
        // assumed, or an English locale shows Indonesian grouping.
        $this->assertStringContainsString('document.documentElement.lang', $script);
        $this->assertStringNotContainsString('id-ID', $script);
        $this->assertStringNotContainsString('en-US', $script);
    }

    public function test_a_line_counts_only_once_both_figures_are_present(): void
    {
        $script = $this->code(file_get_contents(resource_path('js/app.js')));

        // Requiring the price as well is what stops a row with a quantity but no
        // price from reporting a confident "Rp0" and dropping itself out of the
        // order total at the same time.
        $this->assertMatchesRegularExpression(
            '/const entered = q > 0 && p > 0;/',
            $script,
            'A half-filled line must read as unfinished rather than as a zero total.',
        );
    }

    public function test_the_total_follows_the_field_being_edited_rather_than_waiting_for_blur(): void
    {
        $script = $this->code(file_get_contents(resource_path('js/app.js')));

        // `change` fires on blur, which leaves the figure disagreeing with the
        // numbers on screen for as long as the caret is in the field.
        $this->assertMatchesRegularExpression(
            "/addEventListener\('input'/",
            $script,
            'Totals must update while typing, not when the field loses focus.',
        );
    }

    public function test_the_feed_consumption_form_is_left_alone(): void
    {
        // It records a quantity drawn from stock and carries no price on the
        // row, so there is nothing to multiply. A total there would have to be
        // invented.
        $html = $this->get(route('feed-consumptions.create'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-line-amount', $html);
        $this->assertStringNotContainsString('data-line-total', $html);
        $this->assertStringNotContainsString('data-line-grand-total', $html);
    }

    public function test_the_three_total_labels_are_translated(): void
    {
        $dictionary = json_decode(
            file_get_contents(lang_path('id.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        foreach (['Line total', 'Order total', 'Receipt total'] as $key) {
            $this->assertArrayHasKey(
                $key,
                $dictionary,
                'A new label without an Indonesian translation renders its English source on an Indonesian page.',
            );
            $this->assertNotSame($key, $dictionary[$key]);
        }
    }

    /**
     * A source file with its comments removed.
     *
     * The rules here are about code, and the file explains at length why each
     * rule exists — naming the thing it avoids. Asserting against the raw file
     * then matches the explanation and fails for the wrong reason, which is
     * worse than not asserting: the check looks like it guards something it
     * does not.
     */
    private function code(string $source): string
    {
        return trim((string) preg_replace(
            ['#/\*.*?\*/#s', '#//[^\n]*#', '#^\s*\*[^\n]*$#m'],
            '',
            $source,
        ));
    }
}
