<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[WithoutTimestamps]
class MarketPrice extends Model
{
    use Prunable;

    /** Price history is kept for this many days. */
    public const RETENTION_DAYS = 30;

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

    /**
     * Percentage change against the provider's open (rolling 24h for Binance).
     */
    public function changePercent(): ?float
    {
        if ($this->open === null || (float) $this->open === 0.0) {
            return null;
        }

        return round(((float) $this->last - (float) $this->open) / (float) $this->open * 100, 2);
    }

    public function isStale(): bool
    {
        return $this->recorded_at->lt(now()->subSeconds(config('services.market_data.stale_after_seconds')));
    }

    public function prunable(): Builder
    {
        return static::query()->where('recorded_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
