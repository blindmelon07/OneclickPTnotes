<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
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
            'author_id' => User::factory(),
            'type' => fake()->randomElement(Note::types()),
            'data' => [
                'subjective' => fake()->sentence(),
                'blood_pressure' => fake()->numerify('###/##'),
                'heart_rate' => (string) fake()->numberBetween(60, 100),
                'pain_level' => (string) fake()->numberBetween(0, 10),
                'objective_findings' => fake()->paragraph(),
                'assessment' => fake()->paragraph(),
                'goals' => fake()->sentence(),
                'plan' => fake()->sentence(),
                'visit_duration_minutes' => (string) fake()->numberBetween(30, 60),
                'therapist_notes' => fake()->sentence(),
            ],
        ];
    }

    public function signed(): static
    {
        return $this->state([
            'signature_path' => 'signatures/fake-signature.png',
            'signed_at' => now(),
        ]);
    }

    public function emailed(): static
    {
        return $this->signed()->state([
            'pdf_path' => 'notes/fake-note.pdf',
            'emailed_to' => 'office@example.com',
            'emailed_at' => now(),
        ]);
    }
}
