<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionAlternative;
use App\Models\Subject;
use App\Models\QuestionType;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar tipos de questão
        $this->call(QuestionTypeSeeder::class);
        
        // Criar usuários
        $englishTeacher = User::factory()->create([
            'name' => 'Daiane Saraiva',
            'email' => 'professor.ingles@escola.com',
            'password' => bcrypt('senha123'),
        ]);

        $biologyTeacher = User::factory()->create([
            'name' => 'Fábio Nascimento',
            'email' => 'professor.biologia@escola.com',
            'password' => bcrypt('senha123'),
        ]);

        // Criar matérias específicas para cada professor
        $this->call(SubjectSeeder::class);

        // Obter matérias de cada professor
        $englishSubjects = Subject::where('user_id', $englishTeacher->id)->get();
        $biologySubjects = Subject::where('user_id', $biologyTeacher->id)->get();
        $sharedSubjects = Subject::whereNull('user_id')->get();

        $questionTypes = QuestionType::all();

        // ==================== CRIAR TÓPICOS ====================
        
        // Tópicos para Inglês
        $englishTopics = [
            ['name' => 'Simple Present', 'description' => 'Uso do presente simples'],
            ['name' => 'Present Continuous', 'description' => 'Uso do presente contínuo'],
            ['name' => 'Past Simple', 'description' => 'Uso do passado simples'],
            ['name' => 'Present Perfect', 'description' => 'Uso do presente perfeito'],
            ['name' => 'Conditionals', 'description' => 'Estruturas condicionais'],
            ['name' => 'Phrasal Verbs', 'description' => 'Verbos frasais comuns'],
            ['name' => 'Vocabulary - Food', 'description' => 'Vocabulário relacionado a alimentos'],
            ['name' => 'Vocabulary - Travel', 'description' => 'Vocabulário relacionado a viagens'],
            ['name' => 'Reading Comprehension', 'description' => 'Interpretação de textos'],
            ['name' => 'Writing Skills', 'description' => 'Habilidades de escrita'],
        ];

        foreach ($englishTopics as $topic) {
            Topic::create([
                'name' => $topic['name'],
                'description' => $topic['description'],
                'subject_id' => $englishSubjects->first()->id,
                'user_id' => $englishTeacher->id,
            ]);
        }

        // Tópicos para Biologia
        $biologyTopics = [
            ['name' => 'Célula Animal e Vegetal', 'description' => 'Estrutura celular'],
            ['name' => 'Genética Mendeliana', 'description' => 'Leis de Mendel'],
            ['name' => 'Ecologia', 'description' => 'Relações ecológicas'],
            ['name' => 'Sistema Digestório', 'description' => 'Órgãos e funções'],
            ['name' => 'Sistema Respiratório', 'description' => 'Estrutura e função'],
            ['name' => 'Fotossíntese', 'description' => 'Processo fotossintético'],
            ['name' => 'DNA e RNA', 'description' => 'Estrutura dos ácidos nucleicos'],
            ['name' => 'Evolução', 'description' => 'Teorias evolutivas'],
            ['name' => 'Reino Animal', 'description' => 'Classificação dos animais'],
            ['name' => 'Biomas Brasileiros', 'description' => 'Características dos biomas'],
        ];

        foreach ($biologyTopics as $topic) {
            Topic::create([
                'name' => $topic['name'],
                'description' => $topic['description'],
                'subject_id' => $biologySubjects->first()->id,
                'user_id' => $biologyTeacher->id,
            ]);
        }

        // ==================== CRIAR TAGS ====================
        
        // Tags para Inglês
        $englishTags = [
            'grammar', 'vocabulary', 'reading', 'writing', 'listening',
            'speaking', 'verbs', 'nouns', 'adjectives', 'pronunciation',
            'business-english', 'academic-english', 'conversation', 'phrasal-verbs',
            'idioms', 'toefl', 'ielts', 'british-english', 'american-english'
        ];

        // Tags para Biologia
        $biologyTags = [
            'cell-biology', 'genetics', 'ecology', 'anatomy', 'physiology',
            'botany', 'zoology', 'microbiology', 'biochemistry', 'evolution',
            'molecular-biology', 'environmental-science', 'human-biology',
            'marine-biology', 'immunology', 'neuroscience', 'genomics',
            'bioinformatics', 'conservation'
        ];

        foreach ($englishTags as $tagName) {
            Tag::create([
                'name' => $tagName,
                'slug' => Str::slug($tagName),
                //'color' => '#3B82F6',
                'user_id' => $englishTeacher->id,
            ]);
        }

        foreach ($biologyTags as $tagName) {
            Tag::create([
                'name' => $tagName,
                'slug' => Str::slug($tagName),
               // 'color' => '#10B981',
                'user_id' => $biologyTeacher->id,
            ]);
        }

        $allTags = Tag::all();
        $englishTags = Tag::where('user_id', $englishTeacher->id)->get();
        $biologyTags = Tag::where('user_id', $biologyTeacher->id)->get();

        // ==================== QUESTÕES DE INGLÊS ====================
        
        $englishQuestionsData = [
            [
                'statement' => 'Choose the correct alternative to complete the sentence: "She _____ to the gym every day."',
                'explanation' => 'The sentence describes a routine/habit, so we use Simple Present. The correct form for "she" is "goes".',
                'points' => 1.0,
                'subject_id' => $englishSubjects->first()->id,
                'topic_id' => Topic::where('user_id', $englishTeacher->id)->where('name', 'Simple Present')->first()->id,
                'alternatives' => [
                    ['content' => 'go', 'is_correct' => false],
                    ['content' => 'going', 'is_correct' => false],
                    ['content' => 'goes', 'is_correct' => true],
                    ['content' => 'gone', 'is_correct' => false],
                    ['content' => 'went', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'Which sentence is in the Present Continuous tense?',
                'explanation' => 'Present Continuous uses "am/is/are + verb-ing" to describe actions happening now.',
                'points' => 1.0,
                'subject_id' => $englishSubjects->first()->id,
                'topic_id' => Topic::where('user_id', $englishTeacher->id)->where('name', 'Present Continuous')->first()->id,
                'alternatives' => [
                    ['content' => 'I work at a bank.', 'is_correct' => false],
                    ['content' => 'I am working on a project.', 'is_correct' => true],
                    ['content' => 'I worked yesterday.', 'is_correct' => false],
                    ['content' => 'I have worked here for 5 years.', 'is_correct' => false],
                    ['content' => 'I will work tomorrow.', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'What is the past simple form of the verb "to eat"?',
                'explanation' => 'Irregular verbs have specific past forms. "Eat" becomes "ate" in the past simple.',
                'points' => 1.0,
                'subject_id' => $englishSubjects->first()->id,
                'topic_id' => Topic::where('user_id', $englishTeacher->id)->where('name', 'Past Simple')->first()->id,
                'alternatives' => [
                    ['content' => 'eated', 'is_correct' => false],
                    ['content' => 'eaten', 'is_correct' => false],
                    ['content' => 'ate', 'is_correct' => true],
                    ['content' => 'eating', 'is_correct' => false],
                    ['content' => 'eats', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'Complete with the correct phrasal verb: "Could you _____ the music? It\'s too loud."',
                'explanation' => '"Turn down" means to decrease the volume. Other phrasal verbs have different meanings.',
                'points' => 1.5,
                'subject_id' => $englishSubjects->first()->id,
                'topic_id' => Topic::where('user_id', $englishTeacher->id)->where('name', 'Phrasal Verbs')->first()->id,
                'alternatives' => [
                    ['content' => 'turn up', 'is_correct' => false],
                    ['content' => 'turn down', 'is_correct' => true],
                    ['content' => 'turn on', 'is_correct' => false],
                    ['content' => 'turn off', 'is_correct' => false],
                    ['content' => 'turn over', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'Which sentence uses the First Conditional correctly?',
                'explanation' => 'First Conditional: If + present simple, will + base verb. Used for real future possibilities.',
                'points' => 2.0,
                'subject_id' => $englishSubjects->first()->id,
                'topic_id' => Topic::where('user_id', $englishTeacher->id)->where('name', 'Conditionals')->first()->id,
                'alternatives' => [
                    ['content' => 'If it rains, we would cancel the picnic.', 'is_correct' => false],
                    ['content' => 'If it rained, we would cancel the picnic.', 'is_correct' => false],
                    ['content' => 'If it rains, we will cancel the picnic.', 'is_correct' => true],
                    ['content' => 'If it had rained, we would have canceled the picnic.', 'is_correct' => false],
                    ['content' => 'If it will rain, we cancel the picnic.', 'is_correct' => false],
                ]
            ],
        ];

        $englishQuestions = collect();
        foreach ($englishQuestionsData as $qData) {
            $question = Question::create([
                'statement' => $qData['statement'],
                'explanation' => $qData['explanation'],
                'points' => $qData['points'],
                'user_id' => $englishTeacher->id,
                'subject_id' => $qData['subject_id'],
                'topic_id' => $qData['topic_id'],
                'question_type_id' => $questionTypes->where('name', 'Múltipla Escolha')->first()->id,
            ]);

            $order = 1;
            foreach ($qData['alternatives'] as $altData) {
                QuestionAlternative::create([
                    'question_id' => $question->id,
                    'content' => $altData['content'],
                    'is_correct' => $altData['is_correct'],
                    'order' => $order++,
                ]);
            }

            // Adicionar tags relacionadas
            $question->tags()->attach(
                $englishTags->random(rand(2, 4))->pluck('id')->toArray()
            );

            $englishQuestions->push($question);
        }

        // ==================== QUESTÕES DE BIOLOGIA ====================
        
        $biologyQuestionsData = [
            [
                'statement' => 'Qual organela é responsável pela produção de energia na célula?',
                'explanation' => 'A mitocôndria é conhecida como a "usina de energia" da célula, onde ocorre a respiração celular e produção de ATP.',
                'points' => 1.0,
                'subject_id' => $biologySubjects->first()->id,
                'topic_id' => Topic::where('user_id', $biologyTeacher->id)->where('name', 'Célula Animal e Vegetal')->first()->id,
                'alternatives' => [
                    ['content' => 'Ribossomo', 'is_correct' => false],
                    ['content' => 'Mitocôndria', 'is_correct' => true],
                    ['content' => 'Retículo endoplasmático', 'is_correct' => false],
                    ['content' => 'Complexo de Golgi', 'is_correct' => false],
                    ['content' => 'Núcleo', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'Segunda Lei de Mendel (Lei da Segregação Independente) estabelece que:',
                'explanation' => 'A segunda lei afirma que os alelos de diferentes genes segregam-se independentemente durante a formação dos gametas.',
                'points' => 1.5,
                'subject_id' => $biologySubjects->first()->id,
                'topic_id' => Topic::where('user_id', $biologyTeacher->id)->where('name', 'Genética Mendeliana')->first()->id,
                'alternatives' => [
                    ['content' => 'Os alelos se segregam durante a formação dos gametas', 'is_correct' => false],
                    ['content' => 'Os genes localizados em cromossomos diferentes segregam-se independentemente', 'is_correct' => true],
                    ['content' => 'Os caracteres são determinados por pares de fatores', 'is_correct' => false],
                    ['content' => 'Um caráter é determinado por dois fatores', 'is_correct' => false],
                    ['content' => 'Os fatores podem ser dominantes ou recessivos', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'Qual dessas relações ecológicas é um exemplo de mutualismo?',
                'explanation' => 'No mutualismo, ambas as espécies se beneficiam. As micorrizas são associações entre fungos e raízes de plantas onde ambos se beneficiam.',
                'points' => 1.0,
                'subject_id' => $biologySubjects->first()->id,
                'topic_id' => Topic::where('user_id', $biologyTeacher->id)->where('name', 'Ecologia')->first()->id,
                'alternatives' => [
                    ['content' => 'Pulgão e formiga', 'is_correct' => false],
                    ['content' => 'Micorrizas (fungo + raiz)', 'is_correct' => true],
                    ['content' => 'Tênia no intestino humano', 'is_correct' => false],
                    ['content' => 'Orquídea epífita em árvore', 'is_correct' => false],
                    ['content' => 'Leão caçando zebra', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'Em qual parte do sistema digestório ocorre a maior absorção de nutrientes?',
                'explanation' => 'O intestino delgado possui vilosidades intestinais que aumentam a superfície de absorção, permitindo a absorção da maioria dos nutrientes.',
                'points' => 1.0,
                'subject_id' => $biologySubjects->first()->id,
                'topic_id' => Topic::where('user_id', $biologyTeacher->id)->where('name', 'Sistema Digestório')->first()->id,
                'alternatives' => [
                    ['content' => 'Estômago', 'is_correct' => false],
                    ['content' => 'Intestino delgado', 'is_correct' => true],
                    ['content' => 'Intestino grosso', 'is_correct' => false],
                    ['content' => 'Esôfago', 'is_correct' => false],
                    ['content' => 'Boca', 'is_correct' => false],
                ]
            ],
            [
                'statement' => 'Qual gás é liberado durante o processo de fotossíntese?',
                'explanation' => 'Na fotossíntese, as plantas utilizam dióxido de carbono e água para produzir glicose e liberam oxigênio como subproduto.',
                'points' => 1.0,
                'subject_id' => $biologySubjects->first()->id,
                'topic_id' => Topic::where('user_id', $biologyTeacher->id)->where('name', 'Fotossíntese')->first()->id,
                'alternatives' => [
                    ['content' => 'Dióxido de carbono', 'is_correct' => false],
                    ['content' => 'Oxigênio', 'is_correct' => true],
                    ['content' => 'Nitrogênio', 'is_correct' => false],
                    ['content' => 'Metano', 'is_correct' => false],
                    ['content' => 'Hidrogênio', 'is_correct' => false],
                ]
            ],
        ];

        $biologyQuestions = collect();
        foreach ($biologyQuestionsData as $qData) {
            $question = Question::create([
                'statement' => $qData['statement'],
                'explanation' => $qData['explanation'],
                'points' => $qData['points'],
                'user_id' => $biologyTeacher->id,
                'subject_id' => $qData['subject_id'],
                'topic_id' => $qData['topic_id'],
                'question_type_id' => $questionTypes->where('name', 'Múltipla Escolha')->first()->id,
            ]);

            $order = 1;
            foreach ($qData['alternatives'] as $altData) {
                QuestionAlternative::create([
                    'question_id' => $question->id,
                    'content' => $altData['content'],
                    'is_correct' => $altData['is_correct'],
                    'order' => $order++,
                ]);
            }

            // Adicionar tags relacionadas
            $question->tags()->attach(
                $biologyTags->random(rand(2, 4))->pluck('id')->toArray()
            );

            $biologyQuestions->push($question);
        }

        // ==================== CRIAR PROVAS ====================
        
        // Prova de Inglês
        $englishExam = Exam::create([
            'title' => 'Avaliação de Inglês - 1º Bimestre',
            'description' => 'Prova sobre tempos verbais e vocabulário básico',
            'exam_date' => now()->addDays(7),
            'total_points' => 6.5,
            'user_id' => $englishTeacher->id,
            'header_config' => json_encode([
                'school_name' => 'Escola Estadual de Línguas',
                'show_date' => true,
                'show_student_info' => true,
            ]),
            'footer_config' => json_encode([
                'custom_text' => 'Boa prova!',
            ]),
        ]);

        $order = 1;
        foreach ($englishQuestions as $question) {
            $englishExam->questions()->attach($question->id, [
                'order' => $order++,
                'points_override' => null,
            ]);
        }

        // Prova de Biologia
        $biologyExam = Exam::create([
            'title' => 'Avaliação de Biologia - Citologia e Genética',
            'description' => 'Prova sobre organelas celulares e leis de Mendel',
            'exam_date' => now()->addDays(10),
            'total_points' => 5.5,
            'user_id' => $biologyTeacher->id,
            'header_config' => json_encode([
                'school_name' => 'Colégio de Ciências Biológicas',
                'show_date' => true,
                'show_student_info' => true,
            ]),
            'footer_config' => json_encode([
                'custom_text' => 'Responda com atenção!',
            ]),
        ]);

        $order = 1;
        foreach ($biologyQuestions as $question) {
            $biologyExam->questions()->attach($question->id, [
                'order' => $order++,
                'points_override' => null,
            ]);
        }

        // Prova mista (para demonstrar compartilhamento)
        $mixedExam = Exam::create([
            'title' => 'Avaliação Interdisciplinar',
            'description' => 'Questões de inglês técnico e biologia',
            'exam_date' => now()->addDays(14),
            'total_points' => 4.0,
            'user_id' => $englishTeacher->id, // Criada pelo professor de inglês
            'header_config' => json_encode([
                'school_name' => 'Escola Integrada',
                'show_date' => true,
                'show_student_info' => true,
            ]),
            'footer_config' => json_encode([
                'custom_text' => 'Atenção às questões interdisciplinares!',
            ]),
        ]);

        // Adicionar 2 questões de cada
        $mixedQuestions = $englishQuestions->take(2)->merge($biologyQuestions->take(2));
        $order = 1;
        foreach ($mixedQuestions as $question) {
            $mixedExam->questions()->attach($question->id, [
                'order' => $order++,
                'points_override' => null,
            ]);
        }

        $this->command->info('Seeders executados com sucesso!');
        $this->command->info('Usuários criados:');
        $this->command->info('- Professor de Inglês: professor.ingles@escola.com / senha123');
        $this->command->info('- Professor de Biologia: professor.biologia@escola.com / senha123');
        $this->command->info('Cada professor tem 5 questões específicas de sua disciplina.');
    }
}