<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'therapist_id' => User::factory(),
            'visit_type' => fake()->randomElement(Note::types()),
            'scheduled_at' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
