#!/bin/bash

set -e

echo "🚀 Iniciando deploy..."

# Ir para a pasta do projeto
cd /var/www/TestMaker

# Git pull
echo "📥 Atualizando código..."
git pull origin main  # ou master, se for o caso

# Composer - Instalar/atualizar dependências PHP
echo "📦 Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader

# NPM - Instalar dependências do Vue.js
# `npm ci` (não `npm install`): instala exatamente o que está no
# package-lock.json sem reescrevê-lo. `npm install` mexe no lockfile a cada
# execução (hashes/URLs resolvidas), deixando uma mudança local não
# commitada que trava o `git pull` do próximo deploy.
echo "📦 Instalando dependências Node.js..."
npm ci

# NPM - Compilar assets do Vue.js
echo "🔨 Compilando assets do Vue.js..."
npm run build

# Laravel - Limpar caches
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Laravel - Rodar migrations (se houver)
echo "Rodando migrations..."
php artisan migrate --force

# Laravel - Cachear configurações (produção)
echo " Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Ajustar permissões
echo " Ajustando permissões..."
chown -R www-data:www-data /var/www/TestMaker
chmod -R 755 /var/www/TestMaker
chmod -R 775 storage bootstrap/cache

# Reiniciar serviços (se usar queues)
echo " Reiniciando workers..."
php artisan queue:restart

echo " Deploy concluído com sucesso!"
echo " Site: http://72.60.6.221"
