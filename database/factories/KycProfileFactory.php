<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycProfile>
 */
class KycProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => KycStatus::Pending,
            'legal_name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'nationality' => 'KE',
            'id_type' => 'national_id',
            'id_number' => fake()->numerify('########'),
            'address_line1' => fake()->streetAddress(),
            'city' => 'Nairobi',
            'postal_code' => fake()->postcode(),
            'country' => 'KE',
            'submitted_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => ['status' => KycStatus::UnderReview]);
    }
}
