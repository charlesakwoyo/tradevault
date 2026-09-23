<?php

namespace App\Models;

use App\Enums\KycStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Enums\WalletType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Status, verification and acceptance timestamps are intentionally NOT
 * fillable: they only change through dedicated service methods.
 */
#[Fillable(['name', 'email', 'phone', 'country', 'date_of_birth', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Mirrors the column defaults so freshly created instances are complete.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'suspended_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
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

    /** Emails are stored lowercase so uniqueness and login are case-insensitive. */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => Str::lower(trim($value)));
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function kycProfile(): HasOne
    {
        return $this->hasOne(KycProfile::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function portfolioSnapshots(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function wallet(WalletType $type = WalletType::Available, ?string $currency = null): ?Wallet
    {
        $currency ??= config('tradevault.base_currency');

        return $this->wallets->first(
            fn (Wallet $wallet) => $wallet->type === $type && $wallet->currency === $currency
        );
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function isStaff(): bool
    {
        return $this->can('admin.access');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(RoleName::User->value);
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    public function kycStatus(): KycStatus
    {
        return $this->kycProfile?->status ?? KycStatus::NotSubmitted;
    }

    public function isKycApproved(): bool
    {
        return $this->kycStatus() === KycStatus::Approved;
    }
}
