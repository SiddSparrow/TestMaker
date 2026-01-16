# GitHub Actions Workflows

Este diretório contém os workflows de CI/CD para o projeto TestMaker.

## 📋 Workflows Disponíveis

### 1. `build.yml` - Build Simples (Recomendado)

**Quando executa:**
- Push para branches `main` ou `develop`
- Pull requests para `main`

**O que faz:**
- ✅ Instala dependências PHP (Composer)
- ✅ Instala dependências JavaScript (NPM)
- ✅ Compila assets do frontend (Vite)
- ✅ Verifica se os artefatos foram criados
- ✅ Faz upload dos artefatos de build

**Tempo estimado:** ~2-3 minutos

**Use este workflow se:**
- Você só quer garantir que o projeto compila
- Não tem testes automatizados ainda
- Quer um CI rápido e simples

---

### 2. `ci.yml` - CI Completo

**Quando executa:**
- Push para branches `main` ou `develop`
- Pull requests para `main` ou `develop`

**O que faz:**
- ✅ Sobe serviços PostgreSQL e Redis
- ✅ Instala dependências PHP e NPM
- ✅ Compila assets do frontend
- ✅ Roda migrations do banco
- ✅ Executa testes automatizados
- ✅ Verifica code style (Laravel Pint)
- ✅ Análise estática (PHPStan)

**Tempo estimado:** ~5-8 minutos

**Use este workflow se:**
- Você tem testes automatizados
- Quer garantir qualidade de código
- Precisa testar integração com banco de dados

---

## 🚀 Como Usar

### Ativar no GitHub

1. Faça commit dos arquivos `.github/workflows/*.yml`
2. Faça push para o repositório
3. Acesse: `github.com/seu-usuario/TestMaker/actions`
4. Os workflows vão executar automaticamente

### Adicionar Badge no README

**Build Status:**
```markdown
![Build](https://github.com/seu-usuario/TestMaker/workflows/Build/badge.svg)
```

**CI Status:**
```markdown
![CI](https://github.com/seu-usuario/TestMaker/workflows/CI/badge.svg)
```

---

## 🔧 Configurações Necessárias

### Secrets (Opcional)

Se você precisar adicionar secrets (API keys, etc):

1. Vá em: `Settings` → `Secrets and variables` → `Actions`
2. Clique em `New repository secret`
3. Adicione as variáveis necessárias

**Exemplo de secrets úteis:**
- `CLAUDE_KEY` - Para testes de integração com Claude
- `DEPLOY_KEY` - Para deploy automático (futuro)

### Variáveis de Ambiente

Os workflows já incluem as variáveis necessárias para:
- PostgreSQL (test database)
- Redis (cache/queue)
- Laravel (app key gerada automaticamente)

---

## 📊 Visualizando Resultados

### No GitHub

1. Acesse a aba **Actions** do repositório
2. Clique em um workflow específico
3. Veja logs detalhados de cada step

### Localmente (antes de fazer push)

**Simular build do frontend:**
```bash
npm run build
```

**Rodar testes:**
```bash
php artisan test
```

**Verificar code style:**
```bash
./vendor/bin/pint --test
```

---

## 🐛 Troubleshooting

### Erro: "Composer dependencies failed"

**Causa:** Algum pacote do composer não está disponível ou incompatível.

**Solução:**
```bash
# Localmente, rode:
composer validate
composer update --dry-run
```

### Erro: "NPM build failed"

**Causa:** Erro no código Vue ou configuração do Vite.

**Solução:**
```bash
# Localmente, rode:
npm run build
# Veja os erros específicos
```

### Erro: "Tests failed"

**Causa:** Algum teste está falhando.

**Solução:**
```bash
# Localmente, rode:
php artisan test --parallel
# Identifique qual teste está falhando
```

### Erro: "PHPStan failed"

**Causa:** Código com problemas de tipos ou análise estática.

**Solução:**
- No workflow, `continue-on-error: true` está ativo
- O workflow não vai falhar por causa disso
- Mas você deve corrigir os problemas eventualmente

---

## 🔄 Workflow Recomendado para Desenvolvimento

### Para Features/Branches

1. Crie uma branch: `git checkout -b feature/nome-da-feature`
2. Faça suas alterações
3. Commit e push: `git push origin feature/nome-da-feature`
4. **Build workflow roda automaticamente**
5. Abra Pull Request para `main` ou `develop`
6. **CI workflow roda nos PRs**
7. Se tudo estiver verde ✅, faça merge

### Para Hotfixes

1. Crie branch direto da `main`: `git checkout -b hotfix/issue-123`
2. Faça a correção
3. Push e PR direto para `main`
4. Merge após CI passar

---

## 📈 Próximos Passos (Opcional)

### Deploy Automático

Adicione um workflow para deploy:

```yaml
name: Deploy

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    needs: build  # Só roda se build passar

    steps:
      - name: Deploy to production
        run: |
          # Seus comandos de deploy aqui
          echo "Deploy to server..."
```

### Code Coverage

Adicione cobertura de testes:

```yaml
- name: Run tests with coverage
  run: php artisan test --coverage --min=80
```

### Dependabot

Ative o Dependabot para atualizar dependências automaticamente:

Crie `.github/dependabot.yml`:
```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"

  - package-ecosystem: "npm"
    directory: "/"
    schedule:
      interval: "weekly"
```

---

## 📚 Recursos

- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Vite Build Guide](https://vitejs.dev/guide/build.html)

---

**Última atualização:** 2026-01-16
