<?php

namespace Tests\Feature;

use App\Enums\FeedCategory;
use App\Enums\UnitOfMeasure;
use App\Models\FeedItem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A feed item is classified by what the ration uses it for.
 *
 * The category set used to mix three axes at once. Silage sat beside roughage
 * although silage *is* a forage, so the list raised "is silase a roughage?" on
 * every read. By-product classified feeds by where they came from rather than
 * what they do, which split the concentrates across two labels — rice bran and
 * corn germ were filed apart from the other concentrates. Mineral and vitamin
 * were separate from each other when both are supplements.
 *
 * The demo catalogue had seven items, missing most of what an Indonesian dairy
 * actually buys, and named paddy rice as a feed when it is the bran that is
 * fed.
 */
class FeedTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_every_item_uses_a_current_category(): void
    {
        $current = FeedCategory::values();

        foreach (FeedItem::all() as $item) {
            $this->assertContains(
                $item->category->value,
                $current,
                "{$item->code} carries the retired category \"{$item->category->value}\".",
            );
        }
    }

    public function test_the_categories_do_not_overlap(): void
    {
        // Silage is a forage, and a by-product like rice bran is a concentrate.
        // Neither needs its own class once everything sits on one axis.
        $this->assertSame(['forage', 'concentrate', 'supplement', 'other'], FeedCategory::values());

        $this->assertNotContains('silage', FeedCategory::values());
        $this->assertNotContains('byproduct', FeedCategory::values());
        $this->assertNotContains('roughage', FeedCategory::values());
        $this->assertNotContains('mineral', FeedCategory::values());
        $this->assertNotContains('vitamin', FeedCategory::values());
    }

    public function test_silage_and_hay_are_filed_as_forage(): void
    {
        $forage = FeedItem::where('category', FeedCategory::Forage)->pluck('name');

        $this->assertTrue($forage->contains('Silase Jagung'), 'Silage belongs under forage.');
        $this->assertTrue($forage->contains('Hay Padi'), 'Hay belongs under forage.');
    }

    public function test_rice_bran_is_a_concentrate_not_a_by_product(): void
    {
        $item = FeedItem::where('name', 'Limir Beras')->first();

        $this->assertNotNull($item, 'The rice bran item is missing.');
        $this->assertSame(FeedCategory::Concentrate, $item->category);

        // Paddy rice itself is not fed to dairy cattle; the bran is.
        $this->assertFalse(
            FeedItem::pluck('name')->contains('Beras Padi'),
            'Paddy rice is not a dairy feed; its bran is.',
        );
    }

    public function test_minerals_and_vitamins_share_one_supplement_class(): void
    {
        $supplements = FeedItem::where('category', FeedCategory::Supplement)->pluck('name');

        $this->assertTrue($supplements->contains('Mineral Blok Sapi Perah'));
        $this->assertTrue($supplements->contains('Vitamin ADE 1L'));
        $this->assertTrue($supplements->contains('Garam Dapur'));
    }

    public function test_the_catalogue_covers_the_common_dairy_feeds(): void
    {
        // Seven items was too thin to look like a working farm.
        $this->assertGreaterThanOrEqual(12, FeedItem::count(), 'The feed catalogue is too thin to be believable.');

        foreach ([
            'Hay Padi', 'Jerami Padi', 'Silase Jagung',
            'Konsentrat Sapi 8-16', 'Limir Beras', 'Dedak Jagung', 'Jagung Pipil Kering',
            'Bungkil Kedelai', 'Bungkil Kelapa Sawit',
            'Mineral Blok Sapi Perah', 'Garam Dapur',
        ] as $name) {
            $this->assertNotNull(
                FeedItem::where('name', $name)->first(),
                "The catalogue is missing \"{$name}\".",
            );
        }
    }

    public function test_every_category_is_represented(): void
    {
        $used = FeedItem::query()->get()
            ->map(fn (FeedItem $item) => $item->category->value)
            ->unique()->all();

        foreach (['forage', 'concentrate', 'supplement'] as $expected) {
            $this->assertContains($expected, $used, "No item uses the \"{$expected}\" category.");
        }
    }

    public function test_storage_locations_follow_one_naming_convention(): void
    {
        $locations = FeedItem::distinct()->pluck('storage_location');

        foreach ($locations as $location) {
            // Previously "Silo 1" beside "Gudang A" beside "Kantor": two
            // languages and two numbering styles in a single column.
            $this->assertDoesNotMatchRegularExpression(
                '/^Silo\s+\d/',
                $location,
                "Storage \"{$location}\" uses the old \"Silo 1\" style.",
            );

            $this->assertTrue(
                str_starts_with($location, 'Gudang Pakan')
                    || str_starts_with($location, 'Silo Pakan')
                    || str_starts_with($location, 'Padang')
                    || $location === 'Kantor',
                "Storage \"{$location}\" does not follow the shared convention.",
            );
        }
    }

    public function test_bulk_feeds_are_measured_in_kilograms(): void
    {
        foreach (FeedItem::where('category', '!=', FeedCategory::Supplement)->get() as $item) {
            $this->assertSame(
                UnitOfMeasure::Kilogram,
                $item->unit,
                "{$item->code} is not sold by weight.",
            );
        }
    }

    public function test_the_category_filter_offers_exactly_the_current_set(): void
    {
        $html = $this->actingAs($this->owner())
            ->get(route('feed-items.index'))
            ->assertOk()
            ->getContent();

        foreach (FeedCategory::cases() as $category) {
            $this->assertStringContainsString($category->value, $html);
        }

        foreach (['silage', 'byproduct', 'roughage'] as $retired) {
            $this->assertStringNotContainsString('value="'.$retired.'"', $html);
        }
    }
}
