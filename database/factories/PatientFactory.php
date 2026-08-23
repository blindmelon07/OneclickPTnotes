<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\HomeHealthAgency;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'diagnosis' => fake()->sentence(4),
            'doctor_id' => Doctor::factory(),
            'insurance_company_id' => InsuranceCompany::factory(),
            'home_health_agency_id' => HomeHealthAgency::factory(),
            'approved_visits' => fake()->numberBetween(6, 30),
            'cert_period' => fake()->date().' - '.fake()->date(),
            'date_referred' => fake()->dateTimeBetween('-3 months', 'now'),
            'date_of_ie' => fake()->dateTimeBetween('-2 months', 'now'),
            'pt_freq' => fake()->randomElement(['2x/week', '3x/week', '1x/week']),
            'pta_visits' => fake()->numberBetween(0, 10),
            'status' => fake()->randomElement(Patient::statuses()),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => Patient::STATUS_ACTIVE]);
    }

    public function discharged(): static
    {
        return $this->state(['status' => Patient::STATUS_DISCHARGED]);
    }

    public function hospitalized(): static
    {
        return $this->state(['status' => Patient::STATUS_HOSPITALIZED]);
    }
}
