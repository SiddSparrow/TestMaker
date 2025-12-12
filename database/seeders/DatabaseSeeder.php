<?php

namespace Database\Seeders;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionAlternative;
use App\Models\Subject;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar tipos de questão
        $this->call(QuestionTypeSeeder::class);
        
        // Criar matérias
        $this->call(SubjectSeeder::class);

        // Criar usuário de teste
        $user = User::factory()->create([
            'name' => 'Professor Teste',
            'email' => 'professor@teste.com',
        ]);

        // Criar tópicos para cada matéria
        $subjects = Subject::all();
        foreach ($subjects as $subject) {
            Topic::factory(5)->create(['subject_id' => $subject->id]);
        }

        // Criar tags
        $tags = Tag::factory(20)->create();

        // Criar questões
        $questions = Question::factory(50)->create(['user_id' => $user->id]);

        // Adicionar alternativas às questões
        foreach ($questions as $question) {
            // Criar 4 alternativas incorretas
            QuestionAlternative::factory(4)->create([
                'question_id' => $question->id,
            ]);
            
            // Criar 1 alternativa correta
            QuestionAlternative::factory()->correct()->create([
                'question_id' => $question->id,
                'order' => 5,
            ]);

            // Adicionar tags aleatórias
            $question->tags()->attach(
                $tags->random(rand(2, 5))->pluck('id')->toArray()
            );
        }

        // Criar algumas provas de exemplo
        $exams = Exam::factory(5)->create(['user_id' => $user->id]);

        foreach ($exams as $exam) {
            $examQuestions = $questions->random(10);
            $order = 1;
            
            foreach ($examQuestions as $question) {
                $exam->questions()->attach($question->id, [
                    'order' => $order++,
                    'points_override' => null,
                ]);
            }

            // Atualizar pontuação total
            $exam->update([
                'total_points' => $exam->questions->sum('points')
            ]);
        }
    }
}
