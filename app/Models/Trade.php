<?php

namespace App\Models;

use App\Enums\OrderSide;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An execution (fill) of an order. */
class Trade extends Model
{
    use HasUuids;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'side' => OrderSide::class,
            'quantity' => 'decimal:8',
            'price' => 'decimal:8',
            'fee' => 'decimal:8',
            'realized_pnl' => 'decimal:8',
            'executed_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
