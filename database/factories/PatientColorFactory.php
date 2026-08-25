<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientColor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientColor>
 */
class PatientColorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'patient_id' => Patient::factory(),
            'color' => fake()->randomElement(['red', 'blue', 'green', 'purple']),
        ];
    }
}
