<?php

namespace Database\Factories;

use App\Models\HomeHealthAgency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekStart = fake()->dateTimeBetween('-2 months', 'now')->modify('monday this week');

        return [
            'home_health_agency_id' => HomeHealthAgency::factory(),
            'created_by' => User::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('########-####'),
            'week_start_date' => $weekStart,
            'week_end_date' => (clone $weekStart)->modify('+6 days'),
            'status' => Invoice::STATUS_DRAFT,
            'total_amount' => 0,
        ];
    }

    public function sent(): static
    {
        return $this->state([
            'status' => Invoice::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->sent()->state([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }
}
