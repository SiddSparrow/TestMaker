<?php

namespace Database\Seeders;

use App\Models\QuestionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Múltipla Escolha',
                'slug' => 'multipla-escolha',
                'description' => 'Questão com alternativas onde apenas uma é correta',
            ],
            [
                'name' => 'Verdadeiro ou Falso',
                'slug' => 'verdadeiro-falso',
                'description' => 'Questão com afirmações que devem ser classificadas como verdadeiras ou falsas',
            ],
            [
                'name' => 'Dissertativa',
                'slug' => 'dissertativa',
                'description' => 'Questão que requer resposta escrita por extenso',
            ],
            [
                'name' => 'Múltipla Resposta',
                'slug' => 'multipla-resposta',
                'description' => 'Questão com alternativas onde mais de uma pode estar correta',
            ],
        ];

        foreach ($types as $type) {
            QuestionType::create($type);
        }
    }
}
