<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Internal staff note; never shown to the customer. */
#[WithoutTimestamps]
class KycReviewNote extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
