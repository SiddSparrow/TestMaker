<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'main_subject_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'header_config' => [
                'show_logo' => true,
                'school_name' => fake()->company(),
                'show_date' => true,
            ],
            'footer_config' => [
                'show_page_number' => true,
                'custom_text' => 'Boa prova!',
            ],
            'exam_date' => fake()->dateTimeBetween('now', '+1 month'),
            'total_points' => 0,
        ];
    }
}
