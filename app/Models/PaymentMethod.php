<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'supports_deposit' => 'boolean',
            'supports_withdrawal' => 'boolean',
            'currencies' => 'array',
            'min_amount' => 'decimal:8',
            'max_amount' => 'decimal:8',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
