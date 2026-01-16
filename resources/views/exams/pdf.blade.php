<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $exam->title }}</title>
    <style>
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            padding: 15px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
    @php
            // Extrair configurações
            $fontSize = $exam->format_config['font_size'] ?? '12pt';
            $fontFamily = $exam->format_config['font_family'] ?? 'Arial';
            $lineSpacing = $exam->format_config['line_spacing'] ?? '1.5';
            $columns = $exam->format_config['columns'] ?? 1;
            $justifyText = $exam->format_config['justify_text'] ?? false;
            
            // Calcular tamanhos
            $baseSize = (int) str_replace('pt', '', $fontSize);
            $titleSize = ($baseSize + 4) . 'pt';
            $subtitleSize = ($baseSize + 2) . 'pt';
            $smallSize = ($baseSize - 1) . 'pt';
            $tinySize = ($baseSize - 2) . 'pt';
            
            // Alinhamento
            $textAlign = $justifyText ? 'justify' : 'left';
    @endphp
<body style="font-family: {{ $fontFamily }}, sans-serif; font-size: {{ $fontSize }}; line-height: {{ $lineSpacing }}; color: #000;">
    
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #000;">
        @if(isset($exam->header_config['show_logo']) && $exam->header_config['show_logo'])
            <div style="width: 80px; height: 80px; margin: 0 auto 10px; border: 2px dashed #999; text-align: center; line-height: 80px; color: #999; font-size: 10pt;">
                LOGO
            </div>
        @endif

        @if(!empty($exam->header_config['school_name']))
            <div style="font-size: {{ $titleSize }}; font-weight: bold; margin-bottom: 5px;">
                {{ $exam->header_config['school_name'] }}
            </div>
        @endif

        <div style="font-size: {{ $subtitleSize }}; font-weight: bold; margin-bottom: 5px;">
            {{ $exam->title }}
        </div>

        @if($exam->description)
            <div style="font-size: {{ $smallSize }}; color: #333; margin-bottom: 5px;">
                {{ $exam->description }}
            </div>
        @endif

        @if(($exam->header_config['show_date'] ?? true) && $exam->exam_date)
            <div style="font-size: {{ $smallSize }}; color: #666; margin-bottom: 10px;">
                Data: {{ $exam->exam_date->format('d/m/Y') }}
            </div>
        @endif

        @if($exam->header_config['show_student_info'] ?? true)
            <div style="text-align: left; font-size: {{ $smallSize }}; margin: 15px 0; line-height: 1.8;">
                <div>Nome: __________________________________________________________</div>
                <div>Turma: _________________ Data: ____/____/________ Nota: ________</div>
            </div>
        @endif

        <div style="font-weight: bold; margin-top: 10px;">
            Valor Total: {{ number_format($exam->total_points, 1) }} pontos
        </div>
    </div>

    <!-- Questions -->
    <div style="
        @if($columns == 2)
            -webkit-column-count: 2;
            -moz-column-count: 2;
            column-count: 2;
            -webkit-column-gap: 20px;
            -moz-column-gap: 20px;
            column-gap: 20px;
        @endif
    ">
        @foreach($questions as $index => $question)
            @php
                $points = $question->pivot->points_override ?? $question->points;
            @endphp

            <div style="margin-bottom: 20px; page-break-inside: avoid; text-align: {{ $textAlign }};">
                <!-- Question Header -->
                <div style="margin-bottom: 8px;">
                    <span style="font-weight: bold;">{{ $index + 1 }}.</span>
                    <span>{{ $question->statement }}</span>
                    @if($exam->format_config['show_question_points'] ?? true)
                        <span style="font-size: {{ $tinySize }}; font-style: italic; color: #666;">
                            ({{ number_format($points, 1) }} {{ $points == 1 ? 'ponto' : 'pontos' }})
                        </span>
                    @endif
                </div>

                @if($question->alternatives->count() > 0)
                    <!-- Multiple Choice -->
                    <div style="margin-left: 15px; margin-top: 8px;">
                        @foreach($question->alternatives as $altIndex => $alternative)
                            <div style="margin-bottom: 6px; line-height: {{ $lineSpacing }};">
                                <span style="font-weight: bold; margin-right: 5px;">{{ chr(65 + $altIndex) }})</span>
                                <span>{{ $alternative->content }}</span>
                                @if($showAnswers && $alternative->is_correct)
                                    <span style="color: #008000; font-weight: bold;"> ✓ [CORRETA]</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif($exam->format_config['show_answer_space'] ?? true)
                    <!-- Essay/Open Answer -->
                    <div style="margin-left: 15px; margin-top: 8px;">
                        @for($i = 0; $i < 4; $i++)
                            <div style="border-bottom: 1px solid #000; margin-bottom: 12px; height: 15px;"></div>
                        @endfor
                    </div>
                @endif

                @if($showAnswers && $question->explanation)
                    <div style="margin-left: 15px; margin-top: 8px; padding: 8px; background-color: #f0f8ff; border-left: 3px solid #0066cc; font-size: {{ $tinySize }}; font-style: italic;">
                        <div style="font-weight: bold; font-style: normal; margin-bottom: 5px;">Explicação:</div>
                        {{ $question->explanation }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 2px solid #000; font-size: {{ $smallSize }};">
        @if(!empty($exam->footer_config['custom_text']))
            <div>{{ $exam->footer_config['custom_text'] }}</div>
        @endif
        @if($exam->footer_config['show_page_number'] ?? true)
            <div style="margin-top: 8px;">Página 1</div>
        @endif
    </div>

    <!-- Answer Sheet (separate page) -->
    @if(($exam->format_config['separate_answer_sheet'] ?? false) && !$showAnswers)
        @php
            $multipleChoiceQuestions = $questions->filter(fn($q) => $q->alternatives->count() > 0);
        @endphp

        @if($multipleChoiceQuestions->count() > 0)
            <div class="page-break"></div>
            
            <div style="text-align: center;">
                <div style="font-size: {{ $subtitleSize }}; font-weight: bold; margin-bottom: 20px;">
                    FOLHA DE RESPOSTAS
                </div>
                <div style="margin-bottom: 20px;">{{ $exam->title }}</div>
                
                <div style="text-align: left; max-width: 500px; margin: 15px auto; font-size: {{ $smallSize }};">
                    <div>Nome: __________________________________________________________</div>
                    <div>Turma: ________________________ Data: ____/____/________</div>
                </div>

                <div style="margin: 20px auto; max-width: 500px;">
                    @foreach($multipleChoiceQuestions as $mcIndex => $question)
                        <div style="margin-bottom: 12px; padding: 8px; border: 1px solid #ccc; overflow: hidden;">
                            <div style="float: left; font-weight: bold; width: 40px;">{{ $mcIndex + 1 }}.</div>
                            <div style="margin-left: 45px;">
                                @foreach(['A', 'B', 'C', 'D', 'E'] as $letter)
                                    <span style="display: inline-block; width: 25px; height: 25px; border: 2px solid #000; border-radius: 50%; text-align: center; line-height: 25px; font-weight: bold; margin-right: 15px;">
                                        {{ $letter }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

</body>
</html>