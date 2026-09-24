<?php

namespace Database\Seeders;

use App\Enums\AssetClass;
use App\Models\Market;
use Illuminate\Database\Seeder;

/**
 * Reference data: the crypto markets the platform lists. Safe to re-run;
 * existing markets are updated in place and their price history is kept.
 */
class MarketSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: string, 2: int, 3: int, 4: string}> base, name, price precision, quantity precision, min quantity
     */
    private const CRYPTO = [
        ['BTC', 'Bitcoin', 2, 5, '0.00001'],
        ['ETH', 'Ethereum', 2, 4, '0.0001'],
        ['BNB', 'BNB', 2, 3, '0.001'],
        ['SOL', 'Solana', 2, 3, '0.001'],
        ['XRP', 'XRP', 4, 1, '0.1'],
        ['ADA', 'Cardano', 4, 1, '0.1'],
        ['DOGE', 'Dogecoin', 5, 0, '1'],
        ['TRX', 'TRON', 5, 0, '1'],
        ['AVAX', 'Avalanche', 2, 2, '0.01'],
        ['LINK', 'Chainlink', 2, 2, '0.01'],
        ['DOT', 'Polkadot', 3, 2, '0.01'],
        ['LTC', 'Litecoin', 2, 3, '0.001'],
    ];

    public function run(): void
    {
        foreach (self::CRYPTO as [$base, $name, $pricePrecision, $quantityPrecision, $minQuantity]) {
            Market::unguarded(fn () => Market::withTrashed()->updateOrCreate(
                ['symbol' => $base.'-USD'],
                [
                    'name' => $name,
                    'asset_class' => AssetClass::Crypto,
                    'base_asset' => $base,
                    'quote_currency' => 'USD',
                    'price_precision' => $pricePrecision,
                    'quantity_precision' => $quantityPrecision,
                    'min_quantity' => $minQuantity,
                    'data_source' => config('services.market_data.driver'),
                ],
            ));
        }
    }
}
