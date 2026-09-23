<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:8',
            'average_entry_price' => 'decimal:8',
            'realized_pnl' => 'decimal:8',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('quantity', '>', 0);
    }
}
