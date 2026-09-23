<?php

namespace App\Enums;

/**
 * Platform-side ledger accounts. They are the counterparty of every user
 * posting, which keeps each journal balanced (sum of entries = 0). Their
 * balances are expected to be negative/positive mirrors of user funds.
 */
enum SystemAccount: string
{
    // Money received from payment providers, credited to users.
    case DepositsClearing = 'deposits_clearing';
    // Money paid out to users through payment providers.
    case WithdrawalsClearing = 'withdrawals_clearing';
    // Platform revenue from fees.
    case FeeIncome = 'fee_income';
    // Counter-account for audited manual adjustments.
    case Adjustments = 'adjustments';
    // Counterparty for trade settlement with the execution venue.
    case TradingSettlement = 'trading_settlement';

    public function label(): string
    {
        return match ($this) {
            self::DepositsClearing => 'Deposits clearing',
            self::WithdrawalsClearing => 'Withdrawals clearing',
            self::FeeIncome => 'Fee income',
            self::Adjustments => 'Manual adjustments',
            self::TradingSettlement => 'Trading settlement',
        };
    }
}
