<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
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
