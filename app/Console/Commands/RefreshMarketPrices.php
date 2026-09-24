<?php

namespace App\Console\Commands;

use App\Services\MarketData\MarketDataException;
use App\Services\MarketData\MarketPriceRefresher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('markets:refresh-prices')]
#[Description('Fetch the latest price for every active market from the configured market data provider')]
class RefreshMarketPrices extends Command
{
    public function handle(MarketPriceRefresher $refresher): int
    {
        try {
            $result = $refresher->refresh();
        } catch (MarketDataException $e) {
            report($e);
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Updated prices for {$result['updated']} market(s).");

        if ($result['missing'] !== []) {
            $this->components->warn('No quote returned for: '.implode(', ', $result['missing']));
        }

        return self::SUCCESS;
    }
}
