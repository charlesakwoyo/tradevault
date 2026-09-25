<?php

use App\Models\Market;
use App\Models\MarketPrice;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

/**
 * @return array<string, mixed>
 */
function binanceTicker(string $pair, string $last, string $open): array
{
    return [
        'symbol' => $pair, 'lastPrice' => $last, 'openPrice' => $open,
        'bidPrice' => $last, 'askPrice' => $last, 'highPrice' => $last, 'lowPrice' => $open,
        'volume' => '1234.5', 'closeTime' => now()->getTimestampMs(),
    ];
}

test('refreshing with the binance driver stores live prices for active markets', function () {
    config(['services.market_data.driver' => 'binance']);
    $btc = Market::factory()->crypto('BTC', 'Bitcoin')->create();
    Market::factory()->crypto('ETH', 'Ethereum')->inactive()->create();

    Http::fake(['api.binance.com/api/v3/ticker/24hr*' => Http::response([binanceTicker('BTCUSDT', '84279.49', '86288.00')])]);

    $this->artisan('markets:refresh-prices')->assertSuccessful();

    Http::assertSent(fn (Request $request) => $request['symbols'] === '["BTCUSDT"]');

    $price = $btc->latestPrice()->sole();
    expect($price->last)->toBe('84279.49000000')
        ->and($price->source)->toBe('binance')
        ->and($price->is_simulated)->toBeFalse()
        ->and($price->changePercent())->toBe(-2.33)
        ->and($btc->fresh()->data_source)->toBe('binance');
});

test('a failed provider request writes nothing and the command fails', function () {
    config(['services.market_data.driver' => 'binance']);
    Market::factory()->crypto('BTC', 'Bitcoin')->create();

    Http::fake(['api.binance.com/*' => Http::response(['msg' => 'Service unavailable'], 503)]);

    $this->artisan('markets:refresh-prices')->assertFailed();

    expect(MarketPrice::count())->toBe(0);
});

test('the sandbox driver produces prices flagged as simulated', function () {
    config(['services.market_data.driver' => 'sandbox']);
    $market = Market::factory()->create();

    $this->artisan('markets:refresh-prices')->assertSuccessful();

    expect($market->latestPrice()->sole()->is_simulated)->toBeTrue();
});

test('the markets page shows live, delayed and simulated prices distinctly', function () {
    $btc = Market::factory()->crypto('BTC', 'Bitcoin')->create(['data_source' => 'binance']);
    $eth = Market::factory()->crypto('ETH', 'Ethereum')->create(['data_source' => 'binance']);
    $sim = Market::factory()->crypto('SIM', 'Simcoin')->create();

    MarketPrice::unguarded(function () use ($btc, $eth, $sim) {
        $btc->prices()->create(['last' => '84279.49', 'open' => '86288', 'source' => 'binance', 'is_simulated' => false, 'recorded_at' => now()]);
        $eth->prices()->create(['last' => '3000', 'open' => '2900', 'source' => 'binance', 'is_simulated' => false, 'recorded_at' => now()->subHour()]);
        $sim->prices()->create(['last' => '100', 'source' => 'sandbox', 'is_simulated' => true, 'recorded_at' => now()]);
    });

    $this->actingAs($this->customer())->get(route('markets.index'))
        ->assertOk()
        ->assertSee('USD 84,279.49')
        ->assertSee('-2.33%')
        ->assertSee('+3.45%')
        ->assertSee('Live · Binance')
        ->assertSee('Delayed')
        ->assertSee('Simulated');
});

test('old price history is pruned', function () {
    $market = Market::factory()->create();

    MarketPrice::unguarded(function () use ($market) {
        $market->prices()->create(['last' => '1', 'source' => 'sandbox', 'recorded_at' => now()->subDays(MarketPrice::RETENTION_DAYS + 1)]);
        $market->prices()->create(['last' => '2', 'source' => 'sandbox', 'recorded_at' => now()]);
    });

    $this->artisan('model:prune', ['--model' => [MarketPrice::class]])->assertSuccessful();

    expect(MarketPrice::pluck('last')->all())->toBe(['2.00000000']);
});

test('the landing page shows live prices to guests', function () {
    $btc = Market::factory()->crypto('BTC', 'Bitcoin')->create(['data_source' => 'binance']);

    MarketPrice::unguarded(fn () => $btc->prices()->create([
        'last' => '84279.49', 'open' => '86288', 'source' => 'binance', 'is_simulated' => false, 'recorded_at' => now(),
    ]));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('USD 84,279.49')
        ->assertSee('-2.33%')
        ->assertSee('Crypto markets listed')
        ->assertSee('Coming soon');
});
