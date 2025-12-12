<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $subjects = [
            ['name' => 'Matemática', 'color' => '#3B82F6'],
            ['name' => 'Português', 'color' => '#EF4444'],
            ['name' => 'História', 'color' => '#F59E0B'],
            ['name' => 'Geografia', 'color' => '#10B981'],
            ['name' => 'Ciências', 'color' => '#8B5CF6'],
            ['name' => 'Inglês', 'color' => '#EC4899'],
        ];

        $subject = fake()->randomElement($subjects);

        return [
            'name' => $subject['name'],
            'description' => fake()->sentence(),
            'color' => $subject['color'],
        ];
    }
}
