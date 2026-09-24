<?php

namespace App\Services\MarketData;

use Carbon\CarbonImmutable;

/**
 * A single price snapshot from a market-data provider. Decimal values are
 * kept as strings so no precision is lost on the way to the database.
 */
final readonly class Quote
{
    public function __construct(
        public string $last,
        public CarbonImmutable $recordedAt,
        public ?string $bid = null,
        public ?string $ask = null,
        public ?string $open = null,
        public ?string $high = null,
        public ?string $low = null,
        public ?string $volume = null,
    ) {}
}
