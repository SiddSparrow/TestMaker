<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionAlternativeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'content' => fake()->sentence(),
            'is_correct' => false,
            'order' => fake()->numberBetween(1, 5),
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }
}
