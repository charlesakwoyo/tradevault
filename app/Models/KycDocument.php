<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stored on a private disk; only ever streamed through an authorized controller.
 */
class KycDocument extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['*'];

    protected $hidden = ['disk', 'path'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class, 'kyc_profile_id');
    }
}
