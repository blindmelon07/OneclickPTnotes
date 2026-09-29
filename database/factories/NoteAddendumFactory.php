<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\NoteAddendum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteAddendum>
 */
class NoteAddendumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'note_id' => Note::factory()->signed(),
            'author_id' => User::factory(),
            'content' => fake()->paragraph(),
        ];
    }
}
