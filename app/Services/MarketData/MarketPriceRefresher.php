<?php

namespace App\Services\MarketData;

use App\Models\Market;
use App\Models\MarketPrice;
use Illuminate\Support\Facades\DB;

/**
 * Pulls the latest quotes for every active market from the configured
 * provider and stores them. On provider failure nothing is written, so the
 * UI keeps showing the last good price (flagged as delayed once it ages).
 */
class MarketPriceRefresher
{
    public function __construct(private MarketDataProvider $provider) {}

    /**
     * @return array{updated: int, missing: list<string>}
     *
     * @throws MarketDataException
     */
    public function refresh(): array
    {
        $markets = Market::query()->active()->get();
        $quotes = $this->provider->quotes($markets);

        DB::transaction(function () use ($markets, $quotes) {
            foreach ($markets as $market) {
                $quote = $quotes[$market->symbol] ?? null;

                if ($quote === null) {
                    continue;
                }

                MarketPrice::unguarded(fn () => $market->prices()->create([
                    'bid' => $quote->bid,
                    'ask' => $quote->ask,
                    'last' => $quote->last,
                    'open' => $quote->open,
                    'high' => $quote->high,
                    'low' => $quote->low,
                    'volume' => $quote->volume,
                    'source' => $this->provider->name(),
                    'is_simulated' => $this->provider->isSimulated(),
                    'recorded_at' => $quote->recordedAt,
                ]));

                if ($market->data_source !== $this->provider->name()) {
                    $market->forceFill(['data_source' => $this->provider->name()])->save();
                }
            }
        });

        return [
            'updated' => count(array_intersect_key($quotes, $markets->keyBy('symbol')->all())),
            'missing' => $markets->pluck('symbol')->diff(array_keys($quotes))->values()->all(),
        ];
    }
}
