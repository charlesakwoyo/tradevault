<?php

namespace App\Models;

use App\Enums\AssetClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Market extends Model
{
    use SoftDeletes;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'asset_class' => AssetClass::class,
            'min_quantity' => 'decimal:8',
            'max_quantity' => 'decimal:8',
            'is_active' => 'boolean',
            'is_tradable' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'symbol';
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MarketPrice::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(MarketPrice::class)->latestOfMany('recorded_at');
    }

    /** Simulated prices must always be labelled as such in the UI. */
    public function isSimulated(): bool
    {
        return $this->data_source === 'sandbox';
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
