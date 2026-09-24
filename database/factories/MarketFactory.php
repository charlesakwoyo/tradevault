<?php

namespace Database\Factories;

use App\Enums\AssetClass;
use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Market>
 */
class MarketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $base = strtoupper(fake()->unique()->lexify('???'));

        return [
            'symbol' => $base.'-USD',
            'name' => ucfirst(fake()->word()),
            'asset_class' => AssetClass::Crypto,
            'base_asset' => $base,
            'quote_currency' => 'USD',
            'price_precision' => 2,
            'quantity_precision' => 4,
            'min_quantity' => '0.0001',
            'is_active' => true,
            'is_tradable' => true,
            'data_source' => 'sandbox',
        ];
    }

    /**
     * A named crypto market, e.g. crypto('BTC', 'Bitcoin') => BTC-USD.
     */
    public function crypto(string $base, string $name): static
    {
        return $this->state(fn () => ['symbol' => $base.'-USD', 'name' => $name, 'base_asset' => $base]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
