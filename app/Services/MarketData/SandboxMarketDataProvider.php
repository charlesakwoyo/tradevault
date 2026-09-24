<?php

namespace App\Services\MarketData;

use App\Models\Market;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Offline development driver: a small random walk from each market's last
 * recorded price. Every price it produces is flagged as simulated.
 */
class SandboxMarketDataProvider implements MarketDataProvider
{
    private const STARTING_PRICE = '100';

    public function name(): string
    {
        return 'sandbox';
    }

    public function isSimulated(): bool
    {
        return true;
    }

    public function quotes(Collection $markets): array
    {
        $markets->loadMissing('latestPrice');

        $quotes = [];

        foreach ($markets as $market) {
            $previous = $market->latestPrice?->last ?? self::STARTING_PRICE;
            $factor = Money::normalize(1 + random_int(-50, 50) / 10000);

            $quotes[$market->symbol] = new Quote(
                last: Money::mul($previous, $factor),
                recordedAt: CarbonImmutable::now(),
                open: $market->latestPrice?->open ?? $previous,
            );
        }

        return $quotes;
    }
}
