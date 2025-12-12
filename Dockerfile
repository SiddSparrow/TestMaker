FROM php:8.2-fpm

# Instalar dependências do sistema APENAS para PHP/Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libssl-dev \
    # Ferramentas úteis (opcional)
    nano \
    htop \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Verificar se a extensão foi instalada
RUN php -m | grep redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar usuário
RUN useradd -G www-data,root -u 1000 -d /home/laravel laravel
RUN mkdir -p /home/laravel/.composer && \
    chown -R laravel:laravel /home/laravel

# Diretório de trabalho
WORKDIR /var/www/html

# Copiar código
COPY . .

# Remover arquivos Node/Vue se existirem (opcional)
#RUN rm -rf node_modules package.json package-lock.json vite.config.js

# Permissões
RUN chown -R laravel:laravel /var/www/html && \
    chmod -R 775 storage bootstrap/cache

USER laravel

# Instalar dependências PHP
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Gerar chave da aplicação se não existir
RUN if [ ! -f .env ]; then \
    cp .env.example .env && \
    php artisan key:generate; \
    fi

EXPOSE 9000
CMD ["php-fpm"]