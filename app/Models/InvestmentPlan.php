<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Admin-configured product. Has no return/profit-rate fields by design. */
class InvestmentPlan extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:8',
            'max_amount' => 'decimal:8',
            'fee_value' => 'decimal:8',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }
}
