<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'user_id' => User::factory(), // OU User::inRandomOrder()->first()->id
        ];
    }
}
