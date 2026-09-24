<?php

namespace App\Services\MarketData;

use App\Models\Market;
use Illuminate\Support\Collection;

interface MarketDataProvider
{
    /**
     * Identifier stored in markets.data_source and market_prices.source.
     */
    public function name(): string;

    /**
     * Whether prices from this provider are simulated and must be labelled so.
     */
    public function isSimulated(): bool;

    /**
     * Fetch the current quote for each market. Markets the provider does not
     * return are simply absent from the result.
     *
     * @param  Collection<int, Market>  $markets
     * @return array<string, Quote> keyed by market symbol
     *
     * @throws MarketDataException when the provider cannot be reached or answers with an error
     */
    public function quotes(Collection $markets): array;
}
