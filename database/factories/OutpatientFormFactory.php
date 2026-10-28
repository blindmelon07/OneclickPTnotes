<?php

namespace Database\Factories;

use App\Models\OutpatientForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutpatientForm>
 */
class OutpatientFormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $patientName = fake()->name();
        $diagnosis = 'Lumbar sprain / strain';

        return [
            'created_by' => User::factory(),
            'patient_name' => $patientName,
            'diagnosis' => $diagnosis,
            'data' => array_replace_recursive(OutpatientForm::blankData(), [
                'patient_name' => $patientName,
                'age' => '45',
                'dob' => now()->subYears(45)->toDateString(),
                'hand' => 'R-hand',
                'diagnosis' => $diagnosis,
                'doi' => now()->subMonth()->toDateString(),
                'ie' => [
                    'date' => now()->toDateString(),
                    'history' => 'Slipped and fell at work, landing on the lower back.',
                    'chief_complaints' => 'Low back pain radiating to the right leg.',
                    'rom' => 'Lumbar flexion 40, extension 10',
                    'mmt' => 'Trunk 3+/5',
                    'grip' => ['right' => '60', 'left' => '55'],
                    'walk_distance' => '200',
                    'walk_with_device' => 'without',
                    'plan' => [
                        'modalities' => 'Hot pack, e-stim',
                        'therapeutic_exercises' => 'Core strengthening, stretching',
                    ],
                    'rehab_potential' => 'Good',
                    'frequency' => '3',
                    'duration' => '4',
                ],
            ]),
        ];
    }
}
