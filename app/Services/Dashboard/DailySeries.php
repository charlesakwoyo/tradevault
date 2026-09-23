<?php

namespace App\Services\Dashboard;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Builds zero-filled per-day series for charts from real rows.
 */
final class DailySeries
{
    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    public static function sum(Builder $query, string $column, int $days, string $dateColumn = 'created_at'): array
    {
        return self::build($query, "SUM({$column})", $days, $dateColumn);
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    public static function count(Builder $query, int $days, string $dateColumn = 'created_at'): array
    {
        return self::build($query, 'COUNT(*)', $days, $dateColumn);
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private static function build(Builder $query, string $aggregate, int $days, string $dateColumn): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = (clone $query)
            ->where($dateColumn, '>=', $from)
            ->groupBy(DB::raw("DATE({$dateColumn})"))
            ->pluck(DB::raw("{$aggregate} as aggregate"), DB::raw("DATE({$dateColumn}) as day"));

        $labels = [];
        $values = [];

        foreach (CarbonPeriod::create($from, now()->startOfDay()) as $day) {
            $key = $day->toDateString();
            $labels[] = $day->format('M j');
            $values[] = round((float) ($rows[$key] ?? 0), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
