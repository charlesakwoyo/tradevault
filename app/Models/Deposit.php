<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deposit extends Model
{
    use HasUuids;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
            'amount' => 'decimal:8',
            'fee' => 'decimal:8',
            'net_amount' => 'decimal:8',
            'meta' => 'array',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }
}
