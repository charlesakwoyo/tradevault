<?php

namespace App\Models;

use App\Enums\WalletType;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ledger account. Nothing is mass-assignable and `balance` must only be
 * changed by App\Services\Wallet\LedgerService.
 *
 * @property string $balance
 */
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory, HasUuids;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'type' => WalletType::class,
            'balance' => 'decimal:8',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function isSystem(): bool
    {
        return $this->system_code !== null;
    }

    /** System accounts represent money outside the platform and may go negative. */
    public function allowsNegativeBalance(): bool
    {
        return $this->isSystem();
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->getKey());
    }
}
