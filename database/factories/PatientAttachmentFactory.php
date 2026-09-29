<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientAttachment>
 */
class PatientAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word().'.pdf';

        return [
            'patient_id' => Patient::factory(),
            'uploaded_by' => User::factory(),
            'category' => fake()->randomElement(PatientAttachment::categories()),
            'original_filename' => $filename,
            'path' => "attachments/fake/{$filename}",
        ];
    }
}
