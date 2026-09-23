<?php

namespace App\Models;

use App\Enums\FeeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fee extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'type' => FeeType::class,
            'fixed_amount' => 'decimal:8',
            'percentage' => 'decimal:4',
            'min_fee' => 'decimal:8',
            'max_fee' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
