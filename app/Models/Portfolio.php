<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Daily portfolio valuation snapshot, used for performance charts. */
class Portfolio extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'cash_balance' => 'decimal:8',
            'positions_value' => 'decimal:8',
            'total_value' => 'decimal:8',
            'unrealized_pnl' => 'decimal:8',
            'realized_pnl' => 'decimal:8',
            'uses_simulated_prices' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
