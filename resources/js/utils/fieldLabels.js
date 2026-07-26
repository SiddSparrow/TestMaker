// Mapa campo → rótulo em PT-BR para o resumo de erros de validação. Antes o
// resumo mostrava o nome técnico do campo direto do backend — um usuário via
// "alternatives.0.content: campo obrigatório" em vez de "Conteúdo da
// alternativa 1".
const FIELD_LABELS = {
    subject_id: 'Matéria',
    topic_id: 'Tópico',
    question_type_id: 'Tipo de questão',
    statement: 'Enunciado',
    explanation: 'Explicação',
    difficulty_level: 'Dificuldade',
    points: 'Pontos',
    is_active: 'Status',
    tags: 'Etiquetas',
    alternatives: 'Alternativas',
    title: 'Título',
    description: 'Descrição',
    exam_date: 'Data da prova',
    main_subject_id: 'Matéria principal',
    questions: 'Questões',
    header_config: 'Configuração de cabeçalho',
    footer_config: 'Configuração de rodapé',
    format_config: 'Configuração de formatação',
    target_total_points: 'Meta de pontos',
    target_question_count: 'Meta de questões',
};

const NESTED_FIELD_LABELS = {
    content: 'Conteúdo',
    is_correct: 'Alternativa correta',
    order: 'Ordem',
    question_id: 'Questão',
    points_override: 'Pontos',
    topic_id: 'Tópico',
    question_count: 'Quantidade de questões',
};

export function humanizeField(field) {
    const parts = field.split('.');

    if (parts.length === 1) {
        return FIELD_LABELS[parts[0]] || parts[0];
    }

    // Ex.: "alternatives.0.content" -> "Conteúdo (alternativa 1)"
    const [base, index, sub] = parts;
    const baseLabel = FIELD_LABELS[base] || base;
    const position = Number.isNaN(Number(index)) ? null : Number(index) + 1;

    if (sub) {
        const subLabel = NESTED_FIELD_LABELS[sub] || sub;
        return position ? `${subLabel} (${baseLabel.toLowerCase()} ${position})` : `${subLabel} — ${baseLabel}`;
    }

    return position ? `${baseLabel} ${position}` : baseLabel;
}
