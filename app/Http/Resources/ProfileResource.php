<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'phone' => $this->phone,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'country' => ['code' => $this->country, 'name' => Countries::name($this->country)],
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'kyc_status' => $this->kycStatus()->value,
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'terms_accepted_at' => $this->terms_accepted_at?->toIso8601String(),
            'terms_version' => $this->terms_version,
        ];
    }
}
