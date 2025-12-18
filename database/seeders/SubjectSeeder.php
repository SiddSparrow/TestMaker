<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // Obter os dois usuários (serão criados no DatabaseSeeder)
        $englishTeacher = User::where('email', 'professor.ingles@escola.com')->first();
        $biologyTeacher = User::where('email', 'professor.biologia@escola.com')->first();

        // Matérias específicas para cada professor
        $subjects = [
            // Matérias do professor de Inglês
            [
                'name' => 'Língua Inglesa',
                'description' => 'Estudo da língua inglesa, gramática, vocabulário e literatura inglesa',
                'color' => '#3B82F6', // Azul
                'user_id' => $englishTeacher->id 
            ],
            [
                'name' => 'Literatura Inglesa',
                'description' => 'Estudo das obras literárias em língua inglesa',
                'color' => '#1D4ED8', // Azul mais escuro
                'user_id' => $englishTeacher->id
            ],
            
            // Matérias do professor de Biologia
            [
                'name' => 'Biologia',
                'description' => 'Ciência que estuda a vida e os seres vivos',
                'color' => '#10B981', // Verde
                'user_id' => $biologyTeacher->id
            ],
            [
                'name' => 'Ciências da Natureza',
                'description' => 'Estudo integrado de biologia, física e química',
                'color' => '#059669', // Verde mais escuro
                'user_id' => $biologyTeacher->id
            ],
            
            // Matéria compartilhada (opcional)
            /* [
                'name' => 'Redação',
                'description' => 'Produção textual em língua portuguesa',
                'color' => '#EF4444', // Vermelho
                'user_id' => null // Pode ser usada por ambos
            ], */
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}