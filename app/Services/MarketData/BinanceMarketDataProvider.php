<?php

namespace App\Services\MarketData;

use App\Models\Market;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Live crypto prices from Binance's public REST API (no API key needed).
 * One request returns the rolling 24h ticker for every requested market.
 */
class BinanceMarketDataProvider implements MarketDataProvider
{
    /**
     * @param  array<string, string>  $quoteAliases  platform quote currency => Binance quote asset
     */
    public function __construct(
        private string $baseUrl,
        private array $quoteAliases = [],
    ) {}

    public function name(): string
    {
        return 'binance';
    }

    public function isSimulated(): bool
    {
        return false;
    }

    public function quotes(Collection $markets): array
    {
        if ($markets->isEmpty()) {
            return [];
        }

        $symbolsByPair = $markets->mapWithKeys(fn (Market $market) => [$this->pair($market) => $market->symbol]);

        try {
            $tickers = Http::baseUrl($this->baseUrl)
                ->connectTimeout(3)
                ->timeout(10)
                ->retry(
                    [200, 1000],
                    when: fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false,
                )
                ->acceptJson()
                ->get('/api/v3/ticker/24hr', ['symbols' => json_encode($symbolsByPair->keys()->all())])
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException $e) {
            throw new MarketDataException('Binance price request failed: '.$e->getMessage(), previous: $e);
        }

        if (! is_array($tickers)) {
            throw new MarketDataException('Binance returned an unexpected response.');
        }

        $quotes = [];

        foreach ($tickers as $ticker) {
            $symbol = $symbolsByPair[$ticker['symbol'] ?? ''] ?? null;

            if ($symbol === null || ! isset($ticker['lastPrice'])) {
                continue;
            }

            $quotes[$symbol] = new Quote(
                last: $ticker['lastPrice'],
                recordedAt: isset($ticker['closeTime'])
                    ? CarbonImmutable::createFromTimestampMs($ticker['closeTime'])
                    : CarbonImmutable::now(),
                bid: $ticker['bidPrice'] ?? null,
                ask: $ticker['askPrice'] ?? null,
                open: $ticker['openPrice'] ?? null,
                high: $ticker['highPrice'] ?? null,
                low: $ticker['lowPrice'] ?? null,
                volume: $ticker['volume'] ?? null,
            );
        }

        return $quotes;
    }

    /**
     * Binance pair for a market, e.g. BTC quoted in USD reads the BTCUSDT pair.
     */
    public function pair(Market $market): string
    {
        $quote = $this->quoteAliases[$market->quote_currency] ?? $market->quote_currency;

        return strtoupper($market->base_asset.$quote);
    }
}
