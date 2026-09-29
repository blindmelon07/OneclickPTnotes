<?php

namespace Database\Factories;

use App\Models\StagingForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StagingForm>
 */
class StagingFormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $patientName = fake()->name();
        $agencyName = fake()->company().' Home Health';

        return [
            'created_by' => User::factory(),
            'patient_name' => $patientName,
            'agency_name' => $agencyName,
            'data' => array_replace(StagingForm::blankData(), [
                'agency_name' => $agencyName,
                'patient_name' => $patientName,
                'mr_number' => fake()->numerify('MR-#####'),
                'blood_pressure' => '120/80',
                'heart_rate' => '72',
                'diagnosis' => 'Muscle weakness (generalized)',
                'evaluation_date' => now()->toDateString(),
                'problems' => 'Decreased strength and balance; unsafe gait.',
                'short_term_goals' => 'Independent with HEP; safe gait with FWW for 150 ft.',
                'functional_level' => 'MIN assist',
                'plan_of_care' => 'Ther-ex, gait training, balance training, HEP.',
                'pt_frequency' => '2W4',
            ]),
        ];
    }
}
