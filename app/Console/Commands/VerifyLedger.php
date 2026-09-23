<?php

namespace App\Console\Commands;

use App\Models\LedgerEntry;
use App\Models\Wallet;
use App\Services\Wallet\LedgerService;
use App\Support\Money;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Integrity check for the double-entry ledger:
 *  1. every journal's entries sum to zero;
 *  2. every wallet's cached balance equals the sum of its entries;
 *  3. no customer wallet is negative.
 */
#[Signature('ledger:verify')]
#[Description('Verify ledger integrity (balanced journals, balances match entries, no negative customer wallets)')]
class VerifyLedger extends Command
{
    public function handle(LedgerService $ledger): int
    {
        $problems = [];

        LedgerEntry::query()
            ->selectRaw('wallet_transaction_id, SUM(amount) as total')
            ->groupBy('wallet_transaction_id')
            ->havingRaw('SUM(amount) <> 0')
            ->each(function ($row) use (&$problems) {
                $problems[] = "Journal #{$row->wallet_transaction_id} is unbalanced ({$row->total}).";
            });

        Wallet::query()->lazyById(500)->each(function (Wallet $wallet) use ($ledger, &$problems) {
            $derived = $ledger->derivedBalance($wallet);

            if (Money::compare($derived, $wallet->balance) !== 0) {
                $problems[] = "Wallet {$wallet->uuid} balance {$wallet->balance} != ledger {$derived}.";
            }
            if (! $wallet->isSystem() && Money::isNegative($wallet->balance)) {
                $problems[] = "Customer wallet {$wallet->uuid} is negative ({$wallet->balance}).";
            }
        });

        if ($problems === []) {
            $this->components->info('Ledger verified: all journals balanced and all wallet balances match their entries.');

            return self::SUCCESS;
        }

        Log::critical('Ledger verification failed', ['problems' => $problems]);

        foreach ($problems as $problem) {
            $this->components->error($problem);
        }

        return self::FAILURE;
    }
}
