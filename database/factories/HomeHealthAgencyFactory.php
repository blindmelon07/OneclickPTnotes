<?php

namespace Database\Factories;

use App\Models\HomeHealthAgency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeHealthAgency>
 */
class HomeHealthAgencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Home Health',
            'phone' => fake()->phoneNumber(),
            'fax' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'referred_by' => fake()->name(),
        ];
    }
}
