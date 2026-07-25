# TestMaker — Plano de Testes

Este plano cobre toda rota registrada em `routes/web.php` e `routes/auth.php`, mais as funções internas (services, job, policy, model) que sustentam essas rotas. Cada linha aponta para um teste automatizado real em `tests/Feature` ou `tests/Unit` — a suíte inteira roda com:

```
php artisan test
# ou, para um arquivo específico:
php artisan test --filter QuestionTest
```

**Status atual (pós rodada de correções): 138 testes, 381 assertions — 100% passam.** A avaliação original rodou com 40 falhas esperadas (documentando bugs específicos); esta rodada corrigiu todos os itens P0/P1 listados em `docs/avaliacao-completude.md`, e a tabela abaixo foi atualizada para refletir o estado atual. Algumas rotas descritas na avaliação original (`exams.preview`, `exams.export`, `documents.edit`/`update`, `subjects.index`/`create`, `topics.index`/`create`) foram **removidas** por não terem uso real no frontend — ver `docs/avaliacao-completude.md` item P1-7 e P1-5.

Legenda de status: ✅ passa · 🗑️ rota removida (não existe mais, era código morto).

---

## 1. Autenticação (`routes/auth.php`)

Scaffolding do Laravel Breeze, já coberto por testes preexistentes (não escritos neste trabalho, mantidos como estão).

| Rota | Pré-condição | Passos | Resultado esperado | Teste | Status |
|---|---|---|---|---|---|
| GET `register` | guest | acessar página | formulário de cadastro renderiza | `tests/Feature/Auth/RegistrationTest.php` | ✅ |
| POST `register` | guest, dados válidos | enviar formulário | usuário criado, autenticado, redirecionado ao dashboard | `RegistrationTest.php` | ✅ |
| GET `login` | guest | acessar página | formulário de login renderiza | `tests/Feature/Auth/AuthenticationTest.php` | ✅ |
| POST `login` | usuário existente | credenciais corretas / incorretas | autentica e redireciona / erro de validação | `AuthenticationTest.php` | ✅ |
| POST `logout` | autenticado | logout | sessão encerrada, redireciona para `/` | `AuthenticationTest.php` | ✅ |
| GET/POST `forgot-password` | guest | solicitar reset | e-mail de reset enviado (mail fake) | `tests/Feature/Auth/PasswordResetTest.php` | ✅ |
| GET `reset-password/{token}` | token válido | acessar página | formulário de nova senha renderiza | `PasswordResetTest.php` | ✅ |
| POST `reset-password` | token válido | nova senha | senha atualizada, redireciona ao login | `PasswordResetTest.php` | ✅ |
| GET `verify-email` | autenticado, não verificado | acessar página | aviso de verificação renderiza | `tests/Feature/Auth/EmailVerificationTest.php` | ✅ |
| GET `verify-email/{id}/{hash}` | link assinado válido | acessar link | e-mail marcado como verificado | `EmailVerificationTest.php` | ✅ |
| POST `email/verification-notification` | autenticado, não verificado | reenviar | novo e-mail de verificação enviado | *(sem teste dedicado — gap conhecido, não crítico)* | — |
| GET/POST `confirm-password` | autenticado | confirmar senha | sessão marcada como confirmada | `tests/Feature/Auth/PasswordConfirmationTest.php` | ✅ |
| PUT `password` | autenticado | nova senha | senha atualizada | `tests/Feature/Auth/PasswordUpdateTest.php` | ✅ |

---

## 2. Rotas gerais

| Rota | Pré-condição | Passos | Resultado esperado | Teste | Status |
|---|---|---|---|---|---|
| GET `/` | guest | acessar raiz | redireciona para `/dashboard`, que por sua vez redireciona para `/login` (dois saltos — a rota `/` não checa autenticação por si só) | `RouteSmokeTest::test_root_redirects_guest_to_login` | ✅ |
| GET `/` | autenticado | acessar raiz | redireciona para `/dashboard` | `RouteSmokeTest::test_root_redirects_authenticated_user_to_dashboard` | ✅ |
| GET `/dashboard` | guest | acessar | redireciona para `/login` | `RouteSmokeTest::test_dashboard_requires_authentication` | ✅ |
| GET `/dashboard` | autenticado | acessar | página renderiza (200), componente `Dashboard` | `RouteSmokeTest::test_dashboard_renders_for_authenticated_user`, `DashboardTest.php` | ✅ |
| POST `/logout` (única rota `logout`, o `GET` customizado foi removido — P1-9 corrigido) | autenticado | logout | sessão encerrada | `tests/Feature/Auth/AuthenticationTest.php` | ✅ |
| GET/PATCH/DELETE `/profile` | autenticado | ver/editar/excluir perfil | opera normalmente | `tests/Feature/ProfileTest.php` (preexistente) | ✅ |

---

## 3. Dashboard

| Cenário | Passos | Resultado esperado | Teste | Status |
|---|---|---|---|---|
| Estatísticas do usuário | criar questões/provas/matérias para o usuário logado | `stats.total_questions`, `total_exams`, `total_subjects` batem com o que foi criado | `DashboardTest::test_dashboard_shows_the_correct_component_and_stats_for_the_authenticated_user` | ✅ |
| Isolamento entre usuários | criar 5 questões para outro usuário | `stats.total_questions` do usuário logado continua 0 | `DashboardTest::test_dashboard_stats_do_not_include_another_users_data` | ✅ |

---

## 4. Questões (`QuestionController`)

| Rota / Cenário | Pré-condição | Passos | Resultado esperado | Teste | Status | Bug |
|---|---|---|---|---|---|---|
| GET `questions.index` | autenticado, questões próprias e de outro usuário | listar | só aparecem as questões do usuário logado | `QuestionTest::test_index_lists_only_the_authenticated_users_questions` | ✅ | — |
| GET `questions.index?search=` | questões variadas | filtrar por termo | só a questão que casa com a busca aparece | `QuestionTest::test_index_filters_by_search_term` | ✅ | — |
| GET `questions.create` | autenticado | acessar | formulário renderiza | `RouteSmokeTest::test_questions_create_renders` | ✅ | — |
| POST `questions.store` (múltipla escolha) | tipo, matéria, tópico válidos | enviar com 2+ alternativas, 1 correta | questão + alternativas criadas | `QuestionTest::test_store_creates_multiple_choice_question_with_alternatives` | ✅ | — |
| POST `questions.store` (sem alternativa correta) | idem | enviar sem nenhuma `is_correct=true` | 422, erro em `alternatives` | `QuestionTest::test_store_requires_at_least_one_correct_alternative_for_multiple_choice` | ✅ | — |
| POST `questions.store` (dissertativa) | tipo "Dissertativa" | enviar sem alternativas | questão criada | `QuestionTest::test_store_creates_essay_question_without_alternatives` | ✅ | — |
| GET `questions.show` | questão própria | acessar | 200 | `RouteSmokeTest::test_questions_show_renders` | ✅ | — |
| GET `questions.show` | **questão de outro usuário** | acessar | 403 (`QuestionPolicy`) | `QuestionAuthorizationTest::test_a_user_cannot_view_another_users_question` | ✅ | P0-1 (corrigido) |
| GET `questions.edit` | questão própria | acessar | 200 | `RouteSmokeTest::test_questions_edit_renders` | ✅ | — |
| GET `questions.edit` | **questão de outro usuário** | acessar | 403 | `QuestionAuthorizationTest::test_a_user_cannot_open_the_edit_form_for_another_users_question` | ✅ | P0-1 (corrigido) |
| PUT `questions.update` | questão própria | editar alternativas e tags | dados persistidos | `QuestionTest::test_update_replaces_alternatives_and_tags` | ✅ | — |
| PUT `questions.update` | **questão de outro usuário** | tentar editar | 403 | `QuestionAuthorizationTest::test_a_user_cannot_update_another_users_question` | ✅ | P0-1 (corrigido) |
| DELETE `questions.destroy` | questão própria | excluir | `is_active=false` (inativação, não exclusão física) | `QuestionTest::test_destroy_inactivates_instead_of_deleting` | ✅ | — |
| DELETE `questions.destroy` | **questão de outro usuário** | tentar excluir | 403 | `QuestionAuthorizationTest::test_a_user_cannot_inactivate_another_users_question` | ✅ | — (já estava correto) |
| POST `questions.copy` | questão própria | copiar | nova questão criada, respeitando edições feitas no formulário de cópia | `QuestionTest::test_copy_creates_a_duplicate_question` | ✅ | P1-3 (corrigido) |
| POST `questions.copy` | **questão de outro usuário** | tentar copiar | 403 | `QuestionAuthorizationTest::test_a_user_cannot_copy_another_users_question` | ✅ | P0-1 (corrigido) |
| Estatísticas do card de questões | outro usuário com 3 questões próprias | acessar `questions.index` | `stats.total` reflete só as questões do usuário logado | `QuestionAuthorizationTest::test_question_stats_are_scoped_to_the_authenticated_user` | ✅ | P0-3 (corrigido) |
| Dados auxiliares do formulário de criação | outro usuário acessa `questions.create` primeiro | acessar `questions.create` | usuário vê **suas** matérias, não as do outro usuário que carregou a página antes | `QuestionAuthorizationTest::test_create_form_auxiliary_data_is_scoped_to_the_authenticated_user` | ✅ | P0-2 (corrigido) |

## 5. Provas (`ExamController`)

| Rota / Cenário | Pré-condição | Passos | Resultado esperado | Teste | Status | Bug |
|---|---|---|---|---|---|---|
| GET `exams.index` | provas próprias e de outro usuário | listar | só as próprias aparecem | `ExamTest::test_index_lists_only_the_authenticated_users_exams` | ✅ | — |
| GET `exams.create` | autenticado | acessar | 200 | `RouteSmokeTest::test_exams_create_renders` | ✅ | — |
| POST `exams.store` | matéria e questão próprias | payload completo | prova criada, pontos recalculados | `ExamTest::test_store_creates_an_exam_with_questions` | ✅ | — |
| POST `exams.store` | **questão pertence a outro usuário** | incluir o ID da questão alheia | 422 (rejeitar) | `ExamTest::test_store_rejects_questions_owned_by_another_user` | ✅ | P0-4 (corrigido) |
| GET `exams.show` | prova própria | acessar | 200 | `RouteSmokeTest::test_exams_show_renders`, `ExamTest` | ✅ | — |
| GET `exams.show` | **prova de outro usuário** | acessar | 403 (`ExamPolicy`) | `ExamTest::test_a_user_cannot_view_another_users_exam` | ✅ | — |
| GET `exams.edit` | **prova de outro usuário** | acessar | 403 | `ExamTest::test_a_user_cannot_edit_another_users_exam` | ✅ | — |
| GET `exams.questions` | prova própria | acessar (fallback usado por `Exams/Index.vue`) | 200, prop `questions` com as questões da prova | `ExamTest::test_questions_endpoint_returns_the_exams_questions` | ✅ | P1-7 (implementado nesta rodada) |
| GET `exams.questions` | **prova de outro usuário** | acessar | 403 | `ExamTest::test_questions_endpoint_is_forbidden_for_non_owner` | ✅ | — |
| PUT `exams.update` (payload completo) | prova própria | título + `main_subject_id` + questões | atualiza e recalcula pontos | `ExamTest::test_update_with_full_payload_succeeds_and_recalculates_points` | ✅ | — |
| PUT `exams.update` (payload real do `Exams/Edit.vue`) | prova própria | enviar exatamente os campos que o formulário de edição envia | salva sem erro | `ExamTest::test_update_accepts_the_payload_sent_by_the_edit_screen` | ✅ | P1-4 (corrigido) |
| DELETE `exams.destroy` | prova própria, com questões | excluir | soft delete, pivô `exam_questions` removido | `ExamTest::test_destroy_removes_the_exam_and_detaches_questions` | ✅ | — |
| DELETE `exams.destroy` | **prova de outro usuário** | tentar excluir | 403 | `ExamTest::test_a_user_cannot_delete_another_users_exam` | ✅ | — |
| GET `exams.preview` | — | — | 🗑️ rota removida (nunca era chamada pelo frontend; preview real é feito client-side) | — | 🗑️ | P1-7 |
| GET `exams.export` | — | — | 🗑️ rota removida (idem) | — | 🗑️ | P1-7 |
| GET `exams.export.pdf` | prova própria | exportar | PDF baixado (`content-type: application/pdf`) | `ExamTest::test_export_pdf_downloads_for_the_owner`, `RouteSmokeTest` | ✅ | — |
| GET `exams.export.pdf` | **prova de outro usuário** | tentar exportar | 403 | `ExamTest::test_export_pdf_is_forbidden_for_non_owner` | ✅ | — |
| GET `exams.export.docx` | prova própria | exportar | DOCX baixado | `ExamTest::test_export_docx_downloads_for_the_owner` (isolado em processo separado — ver nota) | ✅ | P0-5 (corrigido) |
| POST `exams.toggle-publish` | prova própria | alternar `is_published` | rota registrada e funcional | `ExamTest::test_toggle_publish_route_is_registered` | ✅ | P1-8 (corrigido; falta botão de UI) |
| POST `exams.duplicate` | prova própria | duplicar | rota registrada e funcional | `ExamTest::test_duplicate_route_is_registered` | ✅ | P1-8 (corrigido; falta botão de UI) |

> **Nota sobre a exportação DOCX:** o teste roda com o atributo PHPUnit `#[RunInSeparateProcess]` porque, antes da correção, `ExamController::exportDocx()` chamava `exit()` ao final — sem isolamento, essa chamada teria encerrado o processo do PHPUnit inteiro. Antes da correção o teste falhava com a mensagem *"Test was run in child process and ended unexpectedly"* mesmo isolado; agora passa normalmente, confirmando por execução real que o `exit()` foi removido.

## 6. Documentos (`DocumentController`)

| Rota / Cenário | Pré-condição | Passos | Resultado esperado | Teste | Status | Bug |
|---|---|---|---|---|---|---|
| GET `documents.index` | autenticado | listar | 200 | `RouteSmokeTest::test_documents_index_renders` | ✅ | — |
| GET `documents.create` | autenticado | acessar | 200 | `RouteSmokeTest::test_documents_create_renders` | ✅ | — |
| POST `documents.store` (PDF válido, ≤10MB) | — | upload | documento `status=pending`, job `ProcessDocumentExtractionJob` disparado | `DocumentTest::test_store_uploads_a_valid_document_and_dispatches_the_extraction_job` | ✅ | — |
| POST `documents.store` (tipo inválido) | — | upload `.exe` | 422 | `DocumentTest::test_store_rejects_unsupported_file_types` | ✅ | — |
| POST `documents.store` (>10MB) | — | upload grande | 422 | `DocumentTest::test_store_rejects_files_larger_than_10mb` | ✅ | — |
| GET `documents.show` | documento próprio | acessar | 200 | `RouteSmokeTest::test_documents_show_renders` | ✅ | — |
| GET `documents.show` | **documento de outro usuário** | acessar | 403 | `DocumentTest::test_a_user_cannot_view_another_users_document` | ✅ | — |
| GET `documents.edit` | — | — | 🗑️ rota removida do resource (`->except(['edit','update'])`); não existe página de edição de documento | — | 🗑️ | P1-7 |
| DELETE `documents.destroy` | documento próprio | excluir | arquivo removido do storage + registro apagado | `DocumentTest::test_destroy_deletes_the_stored_file_and_the_record` | ✅ | — |
| DELETE `documents.destroy` | **documento de outro usuário** | tentar excluir | 403 | `DocumentTest::test_a_user_cannot_delete_another_users_document` | ✅ | — |
| POST `documents.reprocess` | documento com `status=failed` | reprocessar | status volta a `pending`, job disparado de novo | `DocumentTest::test_reprocess_resets_status_and_redispatches_the_job` | ✅ | — |
| POST `documents.import-questions` (feliz) | `status=completed`, matéria/tópico/tipo válidos | importar 1 questão | questão criada, `imported_at` preenchido, `status` continua `completed` | `DocumentTest::test_import_questions_creates_questions_and_stamps_imported_at` | ✅ | P1-2 (corrigido) |
| POST `documents.import-questions` (sem matéria) | `status=completed` | importar sem `subject_id` | 422 | `DocumentTest::test_import_questions_requires_a_subject` | ✅ | P1-1 (corrigido) |
| POST `documents.import-questions` (documento não processado) | `status=pending` | tentar importar | erro amigável, nenhuma questão criada | `DocumentTest::test_import_questions_is_forbidden_when_document_not_processed` | ✅ | — |

## 7. Matérias, Tópicos e Tags

Todas as rotas abaixo têm **frontend pronto** (`SubjectsModal.vue`, `TopicsModal.vue`, `TagsModal.vue`), que só usa `store`/`update`/`destroy` — não há páginas Inertia de index/create/show/edit para nenhum dos três, então essas rotas foram **removidas** (eram código morto apontando para métodos que nunca existiriam). Como os models têm global scope por usuário, tentar editar/excluir o registro de outro usuário resulta em **404** (não 403) — o registro simplesmente não existe dentro do escopo do usuário atual.

| Rota / Cenário | Resultado esperado | Teste | Status | Bug |
|---|---|---|---|---|
| GET `subjects.index` / `subjects.create` | — | — | 🗑️ rota removida (sem página correspondente) | — |
| POST `subjects.store` | matéria criada para o usuário | `SubjectTest::test_store_creates_a_subject_for_the_authenticated_user` | ✅ | P1-5 (corrigido) |
| POST `subjects.store` (sem nome) | 422 | `SubjectTest::test_store_requires_a_name` | ✅ | P1-5 |
| PUT `subjects.update` | atualiza | `SubjectTest::test_update_modifies_an_existing_subject` | ✅ | P1-5 |
| PUT `subjects.update` (de outro usuário) | 404 (global scope) | `SubjectTest::test_a_user_cannot_update_another_users_subject` | ✅ | P1-5 |
| DELETE `subjects.destroy` | remove | `SubjectTest::test_destroy_removes_the_subject` | ✅ | P1-5 |
| DELETE `subjects.destroy` (de outro usuário) | 404 | `SubjectTest::test_a_user_cannot_delete_another_users_subject` | ✅ | P1-5 |
| Excluir matéria com questões vinculadas | bloqueado com erro amigável, questões e matéria preservadas | `SubjectTest::test_destroy_is_blocked_when_the_subject_still_has_questions` | ✅ | P1-5a (corrigido) |
| Excluir matéria com questões, no nível do banco | FK `restrict` rejeita a operação (defesa em profundidade) | `SubjectTest::test_deleting_a_subject_with_questions_is_blocked_at_the_database_level` | ✅ | P1-5a |
| GET `topics.index` / `topics.create` | — | — | 🗑️ rota removida (sem página correspondente) | — |
| POST `topics.store` | tópico criado | `TopicTest::test_store_creates_a_topic_for_the_authenticated_user` | ✅ | P1-5 |
| POST `topics.store` (sem matéria/nome) | 422 em ambos | `TopicTest::test_store_requires_a_subject_and_a_name` | ✅ | P1-5 |
| POST `topics.store` (matéria de outro usuário) | 422 | `TopicTest::test_a_user_cannot_create_a_topic_under_another_users_subject` | ✅ | P1-5 |
| PUT `topics.update` | atualiza | `TopicTest::test_update_modifies_an_existing_topic` | ✅ | P1-5 |
| PUT `topics.update` (de outro usuário) | 404 | `TopicTest::test_a_user_cannot_update_another_users_topic` | ✅ | P1-5 |
| DELETE `topics.destroy` | remove, questões mantidas (`topic_id` vira `NULL`, sem cascade) | `TopicTest::test_destroy_removes_the_topic_and_keeps_its_questions` | ✅ | P1-5 |
| DELETE `topics.destroy` (de outro usuário) | 404 | `TopicTest::test_a_user_cannot_delete_another_users_topic` | ✅ | P1-5 |
| `tags.store`/`update`/`destroy` registradas | rotas existem | `TagTest::test_tag_routes_are_registered` | ✅ | P1-6 (corrigido) |
| POST `tags.store` | tag criada | `TagTest::test_store_creates_a_tag_for_the_authenticated_user` | ✅ | P1-6 |
| POST `tags.store` (sem nome) | 422 | `TagTest::test_store_requires_a_name` | ✅ | P1-6 |
| PUT `tags.update` | atualiza | `TagTest::test_update_modifies_an_existing_tag` | ✅ | P1-6 |
| PUT `tags.update` (de outro usuário) | 404 | `TagTest::test_a_user_cannot_update_another_users_tag` | ✅ | P1-6 |
| DELETE `tags.destroy` | remove sem apagar questões | `TagTest::test_destroy_removes_the_tag_without_deleting_its_questions` | ✅ | P1-6 |
| Dois usuários com tag de mesmo nome | ambos conseguem ter uma tag "Gramática" | `TagTest::test_two_different_users_can_have_a_tag_with_the_same_name` | ✅ | P1-6a (corrigido) |

---

## 8. Serviços e funções internas (sem rota HTTP direta)

| Função | Cenário | Resultado esperado | Teste | Status |
|---|---|---|---|---|
| `DocumentParserService::extractText` (.txt) | texto com linhas em branco e espaços extras | texto limpo (colapsado) | `DocumentParserServiceTest::test_extracts_and_cleans_text_from_a_txt_file` | ✅ |
| `DocumentParserService::extractText` (.docx real) | arquivo DOCX gerado com PhpWord | texto extraído corretamente | `DocumentParserServiceTest::test_extracts_text_from_a_real_docx_file` | ✅ |
| `DocumentParserService::extractText` (.docx vazio) | seção sem texto | exceção "DOCX parece estar vazio." | `DocumentParserServiceTest::test_throws_for_empty_docx` | ✅ |
| `DocumentParserService::extractText` (.doc) | qualquer conteúdo | sempre lança exceção (formato não suportado) | `DocumentParserServiceTest::test_doc_format_is_never_supported` | ✅ |
| `DocumentParserService::extractText` (tipo desconhecido) | `.rtf` | exceção "Tipo de arquivo não suportado" | `DocumentParserServiceTest::test_throws_for_unsupported_file_type` | ✅ |
| `DocumentParserService::extractText` (arquivo ausente) | caminho inexistente | exceção "Arquivo não encontrado" | `DocumentParserServiceTest::test_throws_when_file_does_not_exist` | ✅ |
| `DocumentParserService::validateFile` | extensão não suportada / arquivo ausente / >10MB / válido | `false`/`false`/`false`/`true` respectivamente | `DocumentParserServiceTest` (4 testes) | ✅ |
| `ClaudeExtractorService::estimateQuestionCount` | texto sem numeração | retorna 0 | `ClaudeExtractorServiceTest::test_estimate_question_count_returns_zero_for_text_without_numbered_questions` | ✅ |
| `ClaudeExtractorService::estimateQuestionCount` | "QUESTÃO 1..5" | retorna 5 (o maior número explícito) | `ClaudeExtractorServiceTest::test_estimate_question_count_finds_the_highest_explicitly_numbered_question` | ✅ |
| `ClaudeExtractorService::estimateQuestionCount` | 1 questão real + uma linha solta "150) ..." | **superestima para 150** — caracteriza um risco de custo já sinalizado no relatório | `ClaudeExtractorServiceTest::test_estimate_question_count_overestimates_from_a_single_stray_numbered_line` | ✅ *(passa, documentando a limitação)* |
| `ClaudeExtractorService::validateExtraction` | estrutura sem `questions` / enunciado vazio / múltipla escolha com 1 alternativa / confiança fora de 0-1 / questão bem formada | erros específicos em cada caso, nenhum na questão válida | `ClaudeExtractorServiceTest` (5 testes) | ✅ |
| `ProcessDocumentExtractionJob::handle` (sucesso) | parser e extrator mockados retornando dados válidos | documento `completed`, `extraction_result` salvo | `ProcessDocumentExtractionJobTest::test_handle_marks_the_document_completed_and_stores_the_extraction_result` | ✅ |
| `ProcessDocumentExtractionJob::handle` (avisos de validação) | `validateExtraction` retorna erros | avisos mesclados em `metadata.warnings`, documento ainda `completed` | `ProcessDocumentExtractionJobTest::test_handle_merges_validation_warnings_into_the_metadata` | ✅ |
| `ProcessDocumentExtractionJob::handle` (falha no parser) | parser lança exceção | documento `failed`, exceção repropagada | `ProcessDocumentExtractionJobTest::test_handle_marks_the_document_failed_when_text_extraction_throws` | ✅ |
| `ProcessDocumentExtractionJob::handle` (texto vazio) | parser retorna string em branco | documento `failed` com mensagem específica | `ProcessDocumentExtractionJobTest::test_handle_marks_the_document_failed_when_no_text_can_be_extracted` | ✅ |
| `ProcessDocumentExtractionJob::failed` | hook de falha definitiva da fila | documento marcado `failed` com mensagem final | `ProcessDocumentExtractionJobTest::test_failed_hook_marks_the_document_failed_with_a_final_message` | ✅ |
| `ExamPolicy::view/update/delete` | dono vs. não-dono | `true` para o dono, `false` para qualquer outro | `ExamPolicyTest` (2 testes) | ✅ |
| `Exam::boot()` (defaults) | criar prova com configs vazias (`[]`) | preenche `format_config`, `header_config`, `difficulty_distribution` com os padrões | `tests/Unit/Models/ExamTest::test_boot_fills_default_configs_when_created_with_empty_arrays` | ✅ |
| `Exam` casts JSON | salvar e reler `header_config` do banco | permanece array PHP (sem duplo encoding) | `tests/Unit/Models/ExamTest::test_configs_survive_a_round_trip_through_the_database_as_arrays` | ✅ |
| `Exam::recalculateTotalPoints` | 2 questões, uma com `points_override` | soma respeita o override | `tests/Unit/Models/ExamTest::test_recalculate_total_points_sums_points_respecting_overrides` | ✅ |
| `Exam::isComplete` | sem questões / com 1 questão | `false` / `true` | `tests/Unit/Models/ExamTest::test_is_complete_reflects_whether_the_exam_has_questions` | ✅ |

---

## 9. O que este plano não cobre (limitações conhecidas)

- **Chamadas reais à API da Anthropic** — nenhum teste faz uma chamada de rede real ao Claude (custaria dinheiro e tornaria a suíte não-determinística). A lógica pura do `ClaudeExtractorService` é testada via reflection; a integração completa é testada com o serviço mockado em `ProcessDocumentExtractionJobTest`.
- **Frontend Vue/Inertia** — os testes verificam os *props* que cada página Inertia recebe (via `assertInertia`), não o comportamento do JavaScript no navegador. Não há Dusk/Playwright/Cypress configurado neste projeto.
- **`POST email/verification-notification`** — rota de reenvio de verificação de e-mail sem teste dedicado (gap pré-existente, baixo risco).
- **Concorrência/fila real** — os testes de job rodam com `QUEUE_CONNECTION=sync` (padrão de teste do Laravel); não cobrem retry real do worker Redis/database em produção.
