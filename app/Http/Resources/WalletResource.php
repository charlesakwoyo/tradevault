<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wallet */
class WalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'type' => $this->type->value,
            'label' => $this->type->label(),
            'currency' => $this->currency,
            'balance' => $this->balance,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
