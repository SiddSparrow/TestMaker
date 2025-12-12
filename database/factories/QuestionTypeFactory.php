<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QuestionTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Múltipla Escolha',
            'Verdadeiro ou Falso',
            'Dissertativa',
            'Completar Lacunas',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
        ];
    }
}
