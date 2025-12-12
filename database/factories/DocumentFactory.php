<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_name' => fake()->word() . '.pdf',
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'extraction_result' => null,
        ];
    }
}
