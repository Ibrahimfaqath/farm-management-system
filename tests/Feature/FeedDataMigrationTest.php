<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The two data migrations that bring an existing database up to the current
 * schema of values.
 *
 * These exist only for rows that were already written before the enum changed.
 * Every other test in this suite seeds through `DatabaseSeeder`, which writes the
 * current values, so the whole suite passed while the conversion itself was
 * untested — and the feed catalogue stayed broken on a real database with
 * `"silage"`, `"byproduct"`, `"roughage"`, `"mineral"` and `"vitamin"` sitting in
 * the column, throwing `"byproduct" is not a valid backing value for enum
 * App\Enums\FeedCategory` the moment the feed list was opened.
 *
 * The failure is worth being precise about: it was not the migration that was
 * wrong, it was that nothing proved it had ever run. Rows are written straight
 * through `DB::table` here so the values bypass the model's cast, which is the
 * only way to reproduce what a pre-migration database actually contains.
 */
class FeedDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const LEGACY_CATEGORIES = ['silage', 'roughage', 'byproduct', 'mineral', 'vitamin'];

    public function test_the_category_migration_converts_a_database_that_predates_the_enum(): void
    {
        $this->seedLegacyFeedItems();

        $this->assertContains(
            'silage',
            $this->rawCategories(),
            'The legacy rows did not take; this test cannot prove the migration anything.',
        );

        $this->runMigration('2026_10_13_100000_fold_feed_categories_into_one_axis');

        $left = array_intersect($this->rawCategories(), self::LEGACY_CATEGORIES);

        $this->assertSame(
            [],
            array_values($left),
            'These rows still hold a retired value, so reading them throws before the page can render.',
        );
    }

    public function test_the_category_migration_files_the_feed_the_way_the_new_axis_means(): void
    {
        $this->seedLegacyFeedItems();
        $this->runMigration('2026_10_13_100000_fold_feed_categories_into_one_axis');

        // The mapping is the point of the migration, so it is asserted rather
        // than just "nothing invalid is left".
        $this->assertSame('forage', $this->categoryOf('LEGACY-SILAGE'));
        $this->assertSame('forage', $this->categoryOf('LEGACY-ROUGHAGE'));
        $this->assertSame('concentrate', $this->categoryOf('LEGACY-BYPRODUCT'));
        $this->assertSame('supplement', $this->categoryOf('LEGACY-MINERAL'));
        $this->assertSame('supplement', $this->categoryOf('LEGACY-VITAMIN'));
    }

    public function test_the_migration_is_idempotent_because_it_may_run_twice(): void
    {
        $this->seedLegacyFeedItems();
        $migration = $this->runMigration('2026_10_13_100000_fold_feed_categories_into_one_axis');

        $migration->up();

        $this->assertSame([], array_values(array_intersect($this->rawCategories(), self::LEGACY_CATEGORIES)));
        $this->assertSame('forage', $this->categoryOf('LEGACY-SILAGE'), 'A second run moved a row again.');
    }

    public function test_the_unit_migration_converts_bag_everywhere_the_unit_is_stored(): void
    {
        DB::table('feed_items')->insert([
            ['code' => 'UNIT-BAG-A', 'name' => 'A', 'category' => 'forage', 'unit' => 'bag',
                'price_per_unit' => 0, 'reorder_point' => 0, 'reorder_quantity' => 0, 'is_active' => 1,
                'created_at' => now(), 'updated_at' => now()],
            ['code' => 'UNIT-SACK-A', 'name' => 'B', 'category' => 'forage', 'unit' => 'sack',
                'price_per_unit' => 0, 'reorder_point' => 0, 'reorder_quantity' => 0, 'is_active' => 1,
                'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->runMigration('2026_10_13_110000_merge_bag_into_sack');

        // Only `bag` moves. Rewriting `sack` too would be a no-op today and a
        // data-corrupting mistake the day the two stop being interchangeable.
        $this->assertSame('sack', $this->unitOf('UNIT-BAG-A'));
        $this->assertSame('sack', $this->unitOf('UNIT-SACK-A'));
    }

    /**
     * Write rows holding the retired values.
     *
     * Straight through the query builder: touching the model would cast the
     * column and throw before the migration had a chance to fix it, which is
     * exactly the state a pre-migration database is in.
     */
    private function seedLegacyFeedItems(): void
    {
        $rows = [];

        foreach (self::LEGACY_CATEGORIES as $category) {
            $rows[] = [
                'code' => 'LEGACY-'.strtoupper($category),
                'name' => ucfirst($category),
                'category' => $category,
                'unit' => 'kg',
                'price_per_unit' => 0,
                'reorder_point' => 0,
                'reorder_quantity' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('feed_items')->insert($rows);
    }

    /**
     * Load a migration by its filename and run it.
     */
    private function runMigration(string $name): object
    {
        $files = glob(database_path("migrations/{$name}*.php"));

        $this->assertNotEmpty($files, "Migration {$name} not found.");

        $migration = require $files[0];

        $migration->up();

        return $migration;
    }

    /**
     * @return list<string>
     */
    private function rawCategories(): array
    {
        return DB::table('feed_items')
            ->whereIn('code', array_map(fn (string $c) => 'LEGACY-'.strtoupper($c), self::LEGACY_CATEGORIES))
            ->pluck('category')
            ->all();
    }

    private function categoryOf(string $code): ?string
    {
        return DB::table('feed_items')->where('code', $code)->value('category');
    }

    private function unitOf(string $code): ?string
    {
        return DB::table('feed_items')->where('code', $code)->value('unit');
    }
}
