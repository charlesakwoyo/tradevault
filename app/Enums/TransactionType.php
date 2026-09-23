<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Trade = 'trade';
    case TradeProfit = 'trade_profit';
    case TradeLoss = 'trade_loss';
    case Fee = 'fee';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Withdrawal => 'Withdrawal',
            self::Trade => 'Trade',
            self::TradeProfit => 'Trade Profit',
            self::TradeLoss => 'Trade Loss',
            self::Fee => 'Fee',
            self::Refund => 'Refund',
            self::Adjustment => 'Adjustment',
            self::Transfer => 'Internal Transfer',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
