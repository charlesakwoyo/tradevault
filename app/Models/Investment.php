<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    use HasUuids;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'fee' => 'decimal:8',
            'current_value' => 'decimal:8',
            'started_at' => 'datetime',
            'matures_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class, 'investment_plan_id');
    }
}
