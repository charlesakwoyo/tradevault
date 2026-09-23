<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[WithoutTimestamps]
class MarketPrice extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'bid' => 'decimal:8',
            'ask' => 'decimal:8',
            'last' => 'decimal:8',
            'open' => 'decimal:8',
            'high' => 'decimal:8',
            'low' => 'decimal:8',
            'volume' => 'decimal:8',
            'is_simulated' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
