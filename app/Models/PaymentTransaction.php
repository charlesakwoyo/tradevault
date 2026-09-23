<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Raw request/response exchange with a payment provider. */
class PaymentTransaction extends Model
{
    use HasUuids;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
