<?php

namespace Database\Factories;

use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_id' => Subject::factory(),
            'topic_id' => Topic::factory(),
            'question_type_id' => QuestionType::factory(),
            'document_id' => null,
            'statement' => fake()->paragraph() . '?',
            'explanation' => fake()->sentence(),
            'difficulty_level' => fake()->randomElement(['easy', 'medium', 'hard']),
            'points' => fake()->numberBetween(1, 5),
            'is_active' => true,
        ];
    }
}
