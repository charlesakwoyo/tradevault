<?php

namespace App\Models;

use App\Enums\KycStatus;
use Database\Factories\KycProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'legal_name', 'date_of_birth', 'nationality', 'id_type', 'id_number',
    'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
])]
class KycProfile extends Model
{
    /** @use HasFactory<KycProfileFactory> */
    use HasFactory;

    protected $hidden = ['id_number'];

    protected function casts(): array
    {
        return [
            'status' => KycStatus::class,
            'date_of_birth' => 'date',
            'id_number' => 'encrypted',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function reviewNotes(): HasMany
    {
        return $this->hasMany(KycReviewNote::class)->latest('created_at');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
