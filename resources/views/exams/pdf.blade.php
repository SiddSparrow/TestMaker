<!-- resources/views/exams/pdf.blade.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $exam->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 5px 0;
            font-size: 18pt;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16pt;
        }
        .student-info {
            margin: 15px 0;
            text-align: left;
        }
        .question {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .question-number {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .alternative {
            margin-left: 30px;
            margin-top: 8px;
        }
        .correct {
            color: green;
            font-weight: bold;
        }
        .explanation {
            margin-left: 30px;
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f0f0;
            border-left: 3px solid #0066cc;
            font-style: italic;
        }
        .answer-space {
            border-bottom: 1px solid #ccc;
            height: 25px;
            margin: 5px 30px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($exam->header_config['school_name']))
            <h1>{{ $exam->header_config['school_name'] }}</h1>
        @endif
        
        <h2>{{ $exam->title }}</h2>
        
        @if($exam->description)
            <p>{{ $exam->description }}</p>
        @endif
        
        @if(!empty($exam->header_config['show_date']) && $exam->exam_date)
            <p>Data: {{ $exam->exam_date->format('d/m/Y') }}</p>
        @endif
        
        @if(!empty($exam->header_config['show_student_info']))
            <div class="student-info">
                <p>Nome: _______________________________________</p>
                <p>Turma: _____________ Data: ___/___/___ Nota: _______</p>
            </div>
        @endif
        
        <p><strong>Valor Total: {{ $exam->total_points }} pontos</strong></p>
    </div>

    @foreach($questions as $index => $question)
        <div class="question">
            <div class="question-number">
                {{ $index + 1 }}. {{ $question->statement }}
                ({{ $question->pivot->points_override ?? $question->points }} pts)
            </div>
            
            @if($question->alternatives->count() > 0)
                @foreach($question->alternatives as $altIndex => $alt)
                    <div class="alternative {{ $showAnswers && $alt->is_correct ? 'correct' : '' }}">
                        {{ chr(65 + $altIndex) }}) {{ $alt->content }}
                        @if($showAnswers && $alt->is_correct)
                            <strong>✓ CORRETA</strong>
                        @endif
                    </div>
                @endforeach
            @else
                @for($i = 0; $i < 4; $i++)
                    <div class="answer-space"></div>
                @endfor
            @endif
            
            @if($showAnswers && $question->explanation)
                <div class="explanation">
                    <strong>Explicação:</strong> {{ $question->explanation }}
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>{{ $exam->footer_config['custom_text'] ?? 'Boa prova!' }}</p>
    </div>
</body>
</html>