<?php

namespace App\Enums;

/**
 * What a feed item is, for the ration.
 *
 * These sit on one axis — the animal's nutritional role for it — because that
 * is how a ration is actually planned. The previous six mixed three different
 * axes at once, which is what made the feed list tiring to read:
 *
 *   - `silage` was its own category next to `roughage`, although silage *is* a
 *     forage. Two names for one class, and the obvious question becomes "is
 *     silage roughage or not?".
 *   - `byproduct` classified by where a feed came from rather than what it does.
 *     Rice bran and corn germ are concentrates; grouping them apart from the
 *     other concentrates split a single class across two labels.
 *   - `mineral` and `vitamin` were separate from each other when both are
 *     supplements and are bought the same way.
 *
 * The form of a feed still matters — silage and hay are stored and fed
 * differently — so that lives in the item's own name and storage location
 * rather than in a category that claims to be about nutrition.
 */
enum FeedCategory: string
{
    /** Grass, hay, straw and silage: the fibre fraction of the ration. */
    case Forage = 'forage';

    /** Grain, bran and protein meals: the energy and protein fraction. */
    case Concentrate = 'concentrate';

    /** Mineral, vitamin and salt blocks, bought and fed alongside the ration. */
    case Supplement = 'supplement';

    /** Anything that fits no ration role, such as bedding or a preservative. */
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Forage => __('Forage'),
            self::Concentrate => __('Concentrate'),
            self::Supplement => __('Supplement'),
            self::Other => __('Other'),
        };
    }

    /**
     * The order a ration is built in, which is also the order a list should
     * read in: fibre first, then the energy and protein fraction, then the
     * supplements fed alongside it.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
