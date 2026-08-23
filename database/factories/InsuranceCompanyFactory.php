<?php

namespace Database\Factories;

use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsuranceCompany>
 */
class InsuranceCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Insurance',
            'phone' => fake()->phoneNumber(),
        ];
    }
}
