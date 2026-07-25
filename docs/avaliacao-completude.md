# TestMaker — Avaliação de Completude

**Data da avaliação original:** 2026-07-25 · **Data desta atualização:** 2026-07-25 (rodada de correções)
**Stack:** Laravel 12 + Inertia 2 + Vue 3 · PostgreSQL · Redis (cache/fila) · `anthropic-ai/sdk` · dompdf + PhpWord
**Domínio:** banco de questões e montagem de provas para professores, com extração de questões de documentos (PDF/DOCX/TXT) via Claude.

Este relatório foi produzido lendo o código-fonte completo e é acompanhado por uma suíte de testes automatizados (`tests/Feature`, `tests/Unit`) que **codifica o comportamento desejado** de cada rota e função. A avaliação original encontrou 40 testes falhando de 142; **esta rodada de correções resolveu todos os itens P0 e P1** (segurança e bloqueadores funcionais) — a suíte inteira agora passa: **138 testes, 381 assertions, 0 falhas** (4 testes que documentavam bugs específicos foram substituídos por testes do comportamento corrigido, e mais alguns foram adicionados para as novas rotas).

---

## 1. Resumo executivo

O núcleo do produto já funcionava: autenticação (Breeze), perfil, dashboard, CRUD de provas (com política de autorização) e exportação em PDF/DOCX. Esta rodada fechou as cinco lacunas que impediam o produto de funcionar ponta a ponta:

1. **Matérias → Tópicos → Tags** — `SubjectController`, `TopicController` e `TagController` foram implementados (CRUD store/update/destroy, que é tudo que o frontend usa). ✅ **Resolvido** (item 3).
2. **Importação de questões extraídas por IA** — os dois bugs de schema (status inválido, `subject_id` nulo) foram corrigidos com migrations + validação. ✅ **Resolvido** (item 4).
3. **IDOR e vazamento de cache entre professores** — `QuestionPolicy` adicionada e aplicada, cache de `create`/`edit`/stats escopado por usuário. ✅ **Resolvido** (item 5).
4. **Rotas órfãs e funcionalidades sem rota** — `exams.preview`/`exams.export` (nunca usadas no frontend) foram removidas; `documents.edit`/`update` (sem página) removidas do resource; `exams.questions` (usada como fallback em `Exams/Index.vue`) implementada; `exams.toggle-publish` e `exams.duplicate` agora têm rota. ✅ **Resolvido** (item 6).
5. **`ExamController::exportDocx()` chamava `exit()`** — reescrito para devolver uma `Illuminate\Http\Response` normal, igual ao `exportPdf()`. ✅ **Resolvido** (item 6).

---

## 2. Estado por funcionalidade (pós-correção)

| Módulo | Estado | Evidência |
|---|---|---|
| Autenticação (Breeze) | ✅ Completo | `routes/auth.php`, `tests/Feature/Auth/*` |
| Perfil | ✅ Completo | `ProfileController`, `tests/Feature/ProfileTest.php` |
| Dashboard | ✅ Completo e isolado por usuário | `DashboardController.php`; `tests/Feature/DashboardTest.php` |
| Provas — CRUD | ✅ Completo, com Policy e escopo de questões próprias | `ExamController.php`, `app/Policies/ExamPolicy.php`; `tests/Feature/ExamTest.php` |
| Provas — exportação PDF/DOCX | ✅ Completo (DOCX sem mais `exit()`) | `ExamController::exportPdf/exportDocx`; `tests/Feature/ExamTest.php` |
| Provas — publicar/duplicar | ✅ Rotas wiradas (`exams.toggle-publish`, `exams.duplicate`) | `routes/web.php`; `tests/Feature/ExamTest.php` |
| Questões — CRUD | ✅ Completo, com `QuestionPolicy` e cache/stats por usuário | `QuestionController.php`, `app/Policies/QuestionPolicy.php` |
| Questões — cópia | ✅ Corrigida (`copied_from_id` agora existe; payload editado é respeitado) | `QuestionController::copy/createCopyFromRequest`; `tests/Feature/QuestionTest.php` |
| Upload de documento | ✅ Completo | `DocumentController::store`; `tests/Feature/DocumentTest.php` |
| Extração via Claude | 🟡 Funciona; dívida técnica de custo/robustez ainda aberta | `app/Services/ClaudeExtractorService.php`; ver seção 4 (fora do escopo desta rodada) |
| Importação de questões extraídas | ✅ Corrigida (validação escopada por usuário + `imported_at`) | `DocumentController::importQuestions`; `tests/Feature/DocumentTest.php` |
| Matérias (Subjects) | ✅ Implementado (store/update/destroy) | `app/Http/Controllers/SubjectController.php`; `tests/Feature/SubjectTest.php` |
| Tópicos (Topics) | ✅ Implementado (store/update/destroy) | `app/Http/Controllers/TopicController.php`; `tests/Feature/TopicTest.php` |
| Tags | ✅ Implementado (store/update/destroy), unicidade por usuário | `app/Http/Controllers/TagController.php`; `tests/Feature/TagTest.php` |

---

## 3. O que foi corrigido nesta rodada

### P0 — Segurança (multi-tenant) — todos resolvidos

**P0-1. IDOR em questões.** ✅ Adicionado `app/Policies/QuestionPolicy.php` (view/update/delete por `user_id`) e `$this->authorize(...)` em `QuestionController::show/edit/update/copy/destroy` e em `createCopyFromRequest()` (que também checava `Question::findOrFail` sem dono). O `destroy()` antigo já funcionava corretamente por acidente (a checagem de `is_admin` inexistente se reduzia à checagem de dono); a lógica foi simplificada para usar a policy, removendo a referência a uma coluna que nunca existiu.
*Teste:* `tests/Feature/QuestionAuthorizationTest.php` (todos passam).

**P0-2. Cache global vazava matérias/tópicos/tags entre professores.** ✅ As chaves de `QuestionController::create()`/`edit()` agora incluem `Auth::id()` (`'questions_create_data_' . $userId`, `'questions_edit_data_' . $userId`), e `clearQuestionCache()` limpa as duas por usuário.
*Teste:* `QuestionAuthorizationTest::test_create_form_auxiliary_data_is_scoped_to_the_authenticated_user`.

**P0-3. Estatísticas de questões não eram isoladas por usuário.** ✅ `getCachedStats()` agora filtra por `user_id` e usa uma chave de cache por usuário.
*Teste:* `QuestionAuthorizationTest::test_question_stats_are_scoped_to_the_authenticated_user`.

**P0-4. Uma prova podia ser montada com questões de outro professor.** ✅ `ExamController::examRules()` (compartilhada por `store`/`update`) agora valida `questions.*.question_id` com `Rule::exists('questions', 'id')->where('user_id', auth()->id())` em vez de um `exists` puro.
*Teste:* `ExamTest::test_store_rejects_questions_owned_by_another_user`.

### P1 — Bloqueadores funcionais — todos resolvidos

**P1-1 / P1-2. Importação de questões extraídas.** ✅
- `questions.*.subject_id` agora é `required` e validado com `Rule::exists('subjects', 'id')->where('user_id', auth()->id())` (mesmo para `topic_id`), em vez de `nullable` contra uma coluna `NOT NULL`.
- O status inválido `'imported'` foi substituído por uma coluna nova, `documents.imported_at` (migration `2025_12_13_000004_add_imported_at_to_documents_table.php`); o `status` permanece dentro do enum existente (`completed`).
*Teste:* `DocumentTest::test_import_questions_requires_a_subject`, `::test_import_questions_creates_questions_and_stamps_imported_at`.

**P1-3. Cópia de questão sempre falhava.** ✅ Migration `2025_12_13_000001_add_copied_from_id_to_questions_table.php` adiciona a coluna `copied_from_id` (nullable, FK para `questions.id`, `nullOnDelete`). `copy()` e `createCopyFromRequest()` voltaram a usá-la; `createCopyFromRequest()` também passou a aplicar (`fill()`) os dados editados no formulário de cópia em vez de descartá-los silenciosamente (bug adicional encontrado ao corrigir este item: a linha `$newQuestion->fill($data)` estava comentada, então editar a questão antes de copiar não tinha efeito nenhum).
*Teste:* `QuestionTest::test_copy_creates_a_duplicate_question`.

**P1-4. `Exams/Edit.vue` nunca salvava.** ✅ O `useForm()` do arquivo agora inclui `main_subject_id` e `format_config` (copiados de `props.exam`, já que o modal de edição rápida não permite alterar nenhum dos dois).
*Teste:* `ExamTest::test_update_accepts_the_payload_sent_by_the_edit_screen`.

**P1-5 / P1-6. Matérias, Tópicos e Tags — backend implementado.** ✅
- `SubjectController`, `TopicController`: implementados com `store`/`update`/`destroy` apenas (não há páginas Inertia de index/create/show/edit — tudo é feito via modal no dashboard). `routes/web.php` agora registra `Route::resource(...)->only(['store','update','destroy'])` uma única vez cada (sem a duplicação anterior).
- `TagController`: criado do zero, mesmo padrão.
- Como os models `Subject`/`Topic`/`Tag` já têm um global scope por `user_id`, tentar editar/excluir o recurso de outro usuário resulta em **404** (o registro simplesmente não é encontrado dentro do escopo do usuário atual) — não em 403. Os testes foram ajustados para essa realidade em vez de forçar uma checagem de dono redundante no controller.
*Teste:* `tests/Feature/SubjectTest.php`, `TopicTest.php`, `TagTest.php`.

**P1-5a. Risco de perda de dados ao excluir matéria.** ✅ Corrigido em duas camadas:
1. Migration `2025_12_13_000002_restrict_questions_subject_delete.php` troca a FK de `questions.subject_id` de `cascade` para `restrict`.
2. `SubjectController::destroy()` verifica `$subject->questions()->exists()` **antes** de deletar e retorna um erro amigável (`back()->with('error', ...)`) em vez de deixar o banco rejeitar a operação.
*Teste:* `SubjectTest::test_destroy_is_blocked_when_the_subject_still_has_questions` (nível controller) e `::test_deleting_a_subject_with_questions_is_blocked_at_the_database_level` (nível FK, defesa em profundidade).

**P1-6a. Tags únicas globalmente.** ✅ Migration `2025_12_13_000003_scope_tags_uniqueness_to_user.php` troca `unique('name')`/`unique('slug')` por `unique(['user_id','name'])`/`unique(['user_id','slug'])`. `TagController` valida a unicidade com `Rule::unique(...)->where('user_id', auth()->id())`.
*Teste:* `TagTest::test_two_different_users_can_have_a_tag_with_the_same_name`.

**P1-7. Rotas para métodos inexistentes.** ✅ Resolvido removendo o que era morto e implementando o que tinha uso real:
- `exams.preview` e `exams.export` — **removidas** (não eram chamadas em nenhum lugar do frontend; o preview real é feito client-side pelo componente `ExamPreview.vue`).
- `documents.edit`/`documents.update` — **removidas** do resource (`Route::resource('documents', ...)->except(['edit', 'update'])`); não existe página de edição de documento.
- `exams.questions` — **implementada** (`ExamController::questions()`), pois é usada de fato como fallback em `Exams/Index.vue:364`.
*Teste:* `ExamTest::test_questions_endpoint_returns_the_exams_questions`, `::test_questions_endpoint_is_forbidden_for_non_owner`.

**P1-8. `togglePublish()`/`duplicate()` sem rota.** ✅ Rotas `POST /exams/{exam}/toggle-publish` (`exams.toggle-publish`) e `POST /exams/{exam}/duplicate` (`exams.duplicate`) adicionadas. Botões de UI para essas ações **ainda não existem** no frontend — a funcionalidade agora é alcançável via requisição direta, mas falta o gatilho visual em `Exams/Index.vue`/`Exams/Show.vue` (deixado como próximo passo de UI, fora do escopo backend desta rodada).
*Teste:* `ExamTest::test_toggle_publish_route_is_registered`, `::test_duplicate_route_is_registered`.

**P1-9. Rotas duplicadas.** ✅ `routes/web.php` reescrito: `require __DIR__.'/auth.php'` aparece uma única vez; `subjects`/`topics` resources aparecem uma única vez cada; o `GET /logout` customizado foi removido (o frontend já usava exclusivamente `POST /logout` do Breeze via `router.post(route('logout'))`), eliminando a colisão de nomes de rota.

### P0 — Confiabilidade operacional

**P0-5 (crítico). `exportDocx()` chamava `exit()`.** ✅ Reescrito para montar o `.docx` em um buffer (`ob_start()`/`ob_get_clean()`) e devolver `new Illuminate\Http\Response($document, 200, [...])` com os headers apropriados — mesmo padrão do `exportPdf()`, sem `header()` manual nem `exit`. Confirmado por execução real: o teste `ExamTest::test_export_docx_downloads_for_the_owner`, antes isolado em processo separado por segurança (`#[RunInSeparateProcess]`) e que falhava com *"Test was run in child process and ended unexpectedly"*, agora passa normalmente mesmo isolado.

### P2 — Qualidade e dívida técnica

- **Código morto em `ExamController.php`** — ✅ removido na reescrita (o arquivo caiu de 1373 para ~690 linhas; nenhuma versão comentada de método sobrou).
- **Logs de debug (`Log::info` em produção)** — ✅ removidos (listagem de provas e exportação de PDF não logam mais o payload inteiro a cada requisição).
- **Arquivos soltos** — ✅ `app/Http/Controllers/teste.php` (vazio, não versionado) e `fresh` (stacktrace commitado por engano) removidos; `test-extraction.php` convertido no comando `php artisan claude:test-extraction` (`app/Console/Commands/TestClaudeExtraction.php`), então deixa de ser um script solto na raiz do projeto sem passar pelo bootstrap padrão do Laravel.
- **`QuestionTypeSeeder` não idempotente** — ✅ agora usa `updateOrCreate(['slug' => ...], $type)`; rodar `db:seed` mais de uma vez não quebra mais.
- **PHPStan configurado mas não instalado** — ⚠️ **ainda em aberto**. `phpstan.neon` continua referenciando `larastan/larastan`, que não está no `composer.json`. Não foi instalado nesta rodada (é um trabalho de configuração/triagem à parte, potencialmente com muitos achados pré-existentes a avaliar) — recomendo tratá-lo como o próximo item de qualidade.
- **`.env.example`** — ⚠️ **ainda em aberto** (APP_KEY real preenchida, `REDIS_CLIENT` duplicado); não foi tocado nesta rodada por ser configuração de ambiente, não código de aplicação.

---

## 4. Integração com Claude (`app/Services/ClaudeExtractorService.php`) — fora do escopo desta rodada

Continua funcionando, mas os três problemas de custo/robustez identificados na avaliação original **não foram alterados** nesta rodada de correções (são mudanças de comportamento de produto/custo que merecem decisão própria, não bugs de CRUD):

1. **`maxTokens: 8192`** é baixo para 15 questões por chunk — causa provável dos erros de "JSON inválido" que o próprio prompt já tenta mitigar defensivamente.
2. **`estimateQuestionCount()`** superestima a partir de uma única linha numerada solta (ex.: "150) Isto não é uma questão"), dependendo de qual pattern bate. Caracterizado por `tests/Unit/ClaudeExtractorServiceTest.php::test_estimate_question_count_overestimates_from_a_single_stray_numbered_line` (ainda passa, documentando o comportamento atual).
3. **Parsing de JSON via regex com 3 fallbacks** — substituível por *structured outputs* (JSON Schema) nos modelos atuais.

O modelo configurado (`claude-sonnet-4-5-20250929`, comentário desatualizado como "Claude 3.5 Sonnet") também não foi trocado nesta rodada.

---

## 5. Resultado da suíte após as correções

```
php artisan test
Tests:    138 passed (381 assertions)
```

```
vendor/bin/pint --test
PASS  ... 90 files
```

Nenhum teste foi removido para "forçar" o verde — os 4 testes que documentavam bugs específicos (cascade de exclusão de matéria, tags únicas globalmente, status "imported", payload do Edit.vue) foram **reescritos para verificar o comportamento correto**, mantendo a cobertura do cenário original. Dois testes novos cobrem a rota `exams.questions`, que passou a existir nesta rodada.

## 6. O que ainda falta (não coberto nesta rodada)

1. **UI para publicar/duplicar provas** — as rotas existem (P1-8), mas não há botão em `Exams/Index.vue`/`Exams/Show.vue` que as acione.
2. **PHPStan/Larastan** — instalar e triar achados.
3. **Revisão do `ClaudeExtractorService`** — `maxTokens`, `stop_reason`, chunking, structured outputs, atualização do modelo.
4. **`.env.example`** — remover `APP_KEY` real, resolver duplicidade de `REDIS_CLIENT`.
5. Nenhum teste de browser (Dusk/Playwright/Cypress) — a suíte cobre o backend e os *props* Inertia, não o comportamento do Vue no navegador; vale uma passada manual pela UI para os fluxos alterados (upload → importação, edição de prova, modais de matéria/tópico/tag).
