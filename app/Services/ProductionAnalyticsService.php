<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\MilkProduction;
use Illuminate\Support\Carbon;

/**
 * Production analytics. Declines are reported as facts for humans to
 * investigate; the system never infers a medical cause.
 */
class ProductionAnalyticsService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Daily totals for a farm.
     *
     * @return list<array{date: string, total: float, records: int}>
     */
    public function dailyTotals(?int $farmId, Carbon $from, Carbon $to): array
    {
        return MilkProduction::query()
            ->forFarm($farmId)
            ->between($from->toDateString(), $to->toDateString())
            ->groupBy('production_date')
            ->orderBy('production_date')
            ->selectRaw('production_date, SUM(liters) as total, COUNT(*) as records')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->production_date,
                'total' => round((float) $row->total, 2),
                'records' => (int) $row->records,
            ])
            ->all();
    }

    /**
     * Total liters and per-head averages over a window.
     *
     * @return array{total: float, records: int, days: int, per_day: float, per_lactating_head: float}
     */
    public function summary(?int $farmId, Carbon $from, Carbon $to): array
    {
        $total = (float) MilkProduction::query()
            ->forFarm($farmId)
            ->between($from->toDateString(), $to->toDateString())
            ->sum('liters');

        $records = MilkProduction::query()
            ->forFarm($farmId)
            ->between($from->toDateString(), $to->toDateString())
            ->count();

        $days = max(1, $from->diffInDays($to) + 1);
        $lactating = Animal::query()->active()->lactating()->when($farmId, fn ($q) => $q->where('farm_id', $farmId))->count();

        return [
            'total' => round($total, 2),
            'records' => $records,
            'days' => $days,
            'per_day' => round($total / $days, 2),
            'per_lactating_head' => $lactating > 0 ? round($total / $lactating, 2) : 0.0,
        ];
    }

    /**
     * Compare two equal-length windows.
     *
     * @return array{current: float, previous: float, difference: float, change_pct: float|null, direction: string}
     */
    public function compareWindows(?int $farmId, Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to) + 1;
        $previousTo = $from->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($days - 1);

        $current = $this->summary($farmId, $from, $to)['per_day'];
        $previous = $this->summary($farmId, $previousFrom, $previousTo)['per_day'];

        $changePct = $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => round($current - $previous, 2),
            'change_pct' => $changePct,
            'direction' => match (true) {
                $changePct === null => 'unknown',
                $changePct > 1 => 'up',
                $changePct < -1 => 'down',
                default => 'flat',
            },
        ];
    }

    /**
     * Animals whose average daily yield dropped noticeably between two windows.
     * Reported as an observation only.
     *
     * @return list<array{animal: Animal, previous: float, current: float, change_pct: float}>
     */
    public function decliningAnimals(?int $farmId = null, int $thresholdPct = 10, int $windowDays = 14): array
    {
        $to = now();
        $from = $to->copy()->subDays($windowDays - 1);
        $previousFrom = $from->copy()->subDays($windowDays);

        $current = $this->perAnimalTotals($farmId, $from, $to);
        $previous = $this->perAnimalTotals($farmId, $previousFrom, $from->copy()->subDay());

        $declines = [];

        foreach ($current as $animalId => $totals) {
            $currentAvg = $totals['total'] / $totals['days'];

            if (! isset($previous[$animalId])) {
                continue;
            }

            $previousAvg = $previous[$animalId]['total'] / $previous[$animalId]['days'];

            if ($previousAvg <= 0) {
                continue;
            }

            $change = round(($currentAvg - $previousAvg) / $previousAvg * 100, 1);

            if ($change <= -$thresholdPct) {
                $animal = Animal::find($animalId);

                if ($animal) {
                    $declines[] = [
                        'animal' => $animal,
                        'previous' => round($previousAvg, 2),
                        'current' => round($currentAvg, 2),
                        'change_pct' => $change,
                    ];
                }
            }
        }

        usort($declines, fn ($a, $b) => $a['change_pct'] <=> $b['change_pct']);

        return $declines;
    }

    /**
     * @return array<int, array{total: float, days: int, records: int}>
     */
    private function perAnimalTotals(?int $farmId, Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to) + 1;

        return MilkProduction::query()
            ->forFarm($farmId)
            ->between($from->toDateString(), $to->toDateString())
            ->whereNotNull('animal_id')
            ->groupBy('animal_id')
            ->selectRaw('animal_id, SUM(liters) as total, COUNT(DISTINCT production_date) as days, COUNT(*) as records')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->animal_id => [
                    'total' => round((float) $row->total, 2),
                    'days' => max(1, (int) $row->days),
                    'records' => (int) $row->records,
                ],
            ])
            ->all();
    }

    /**
     * Production grouped by barn.
     *
     * @return list<array{barn: string, total: float, records: int}>
     */
    public function byBarn(?int $farmId, Carbon $from, Carbon $to): array
    {
        return MilkProduction::query()
            ->forFarm($farmId)
            ->between($from->toDateString(), $to->toDateString())
            ->whereNotNull('barn_id')
            ->join('barns', 'barns.id', '=', 'milk_productions.barn_id')
            ->groupBy('barns.id', 'barns.name')
            ->orderByDesc('total')
            ->selectRaw('barns.name as barn, SUM(milk_productions.liters) as total, COUNT(*) as records')
            ->get()
            ->map(fn ($row) => [
                'barn' => $row->barn,
                'total' => round((float) $row->total, 2),
                'records' => (int) $row->records,
            ])
            ->all();
    }

    /**
     * Top producing animals over a window.
     *
     * @return list<array{animal: Animal, total: float, days: int, average: float}>
     */
    public function topAnimals(?int $farmId, Carbon $from, Carbon $to, int $limit = 10): array
    {
        $days = $from->diffInDays($to) + 1;

        return MilkProduction::query()
            ->forFarm($farmId)
            ->between($from->toDateString(), $to->toDateString())
            ->whereNotNull('animal_id')
            ->groupBy('animal_id')
            ->selectRaw('animal_id, SUM(liters) as total, COUNT(DISTINCT production_date) as days')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($days): ?array {
                $animal = Animal::find($row->animal_id);

                if (! $animal) {
                    return null;
                }

                return [
                    'animal' => $animal,
                    'total' => round((float) $row->total, 2),
                    'days' => max(1, (int) $row->days),
                    'average' => round((float) $row->total / max(1, $days), 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
