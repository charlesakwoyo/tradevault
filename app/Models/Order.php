<?php

namespace App\Models;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'side' => OrderSide::class,
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'quantity' => 'decimal:8',
            'price' => 'decimal:8',
            'stop_price' => 'decimal:8',
            'filled_quantity' => 'decimal:8',
            'average_fill_price' => 'decimal:8',
            'fees' => 'decimal:8',
            'reserved_amount' => 'decimal:8',
            'filled_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Open, OrderStatus::PartiallyFilled], true);
    }
}
