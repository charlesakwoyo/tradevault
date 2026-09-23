<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One line of a double-entry posting. Append-only.
 *
 * @property string $amount
 * @property string $balance_after
 */
#[WithoutTimestamps]
class LedgerEntry extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'balance_after' => 'decimal:8',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Ledger entries are immutable.'));
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
