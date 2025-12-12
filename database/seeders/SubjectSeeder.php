<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matemática', 'description' => 'Disciplina de exatas', 'color' => '#3B82F6'],
            ['name' => 'Português', 'description' => 'Língua portuguesa', 'color' => '#EF4444'],
            ['name' => 'História', 'description' => 'Estudo do passado', 'color' => '#F59E0B'],
            ['name' => 'Geografia', 'description' => 'Estudo do espaço', 'color' => '#10B981'],
            ['name' => 'Ciências', 'description' => 'Ciências naturais', 'color' => '#8B5CF6'],
            ['name' => 'Inglês', 'description' => 'Língua inglesa', 'color' => '#EC4899'],
            ['name' => 'Física', 'description' => 'Estudo da natureza', 'color' => '#06B6D4'],
            ['name' => 'Química', 'description' => 'Estudo da matéria', 'color' => '#14B8A6'],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
