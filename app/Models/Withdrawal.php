<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Withdrawal extends Model
{
    use HasUuids;

    protected $guarded = ['*'];

    protected $hidden = ['destination'];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount' => 'decimal:8',
            'fee' => 'decimal:8',
            'net_amount' => 'decimal:8',
            'destination' => 'encrypted',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }
}
