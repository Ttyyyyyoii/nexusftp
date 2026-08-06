# ================================================================
# Étape 1 : Builder le frontend Vue.js
# ================================================================
FROM node:20-alpine AS builder

WORKDIR /app

# Copier les fichiers de dépendances en premier (optimise le cache Docker)
COPY package.json package-lock.json ./
RUN npm ci

# Copier le reste du code source Vue
COPY src/ ./src/
COPY public/ ./public/
COPY index.html vite.config.js vite.config.ts postcss.config.js tailwind.config.js components.json tsconfig*.json ./

# Builder pour la production
RUN npm run build

# ================================================================
# Étape 2 : Serveur Apache + PHP avec toutes les extensions nécessaires
# ================================================================
FROM php:8.2-apache

# Installer les dépendances système pour ssh2, ftp, GD et zip
RUN apt-get update && apt-get install -y \
    libssh2-1-dev \
    libssh2-1 \
    libcurl4-openssl-dev \
    pkg-config \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Installer l'extension PHP ssh2 via PECL
RUN pecl install ssh2-1.4 \
    && docker-php-ext-enable ssh2

# Activer l'extension ftp (intégrée à PHP, juste besoin d'activer)
RUN docker-php-ext-install ftp

# Installer GD pour l'optimisation d'images (JPEG + PNG)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Installer zip pour l'extraction des archives GitHub
RUN docker-php-ext-install zip

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers

# Configurer Apache pour la gestion des routes Vue.js (SPA)
# Le site web sera servi depuis /var/www/html
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Configuration PHP optimisée pour les transferts de fichiers volumineux
RUN echo "upload_max_filesize = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "output_buffering = Off" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "implicit_flush = On" >> /usr/local/etc/php/conf.d/uploads.ini

# Copier le backend PHP dans /var/www/html/api
# (ceci correspond à l'URL /api/ que ton code Vue.js appelle)
COPY ftp_backend/ /var/www/html/api/

# Copier le frontend Vue.js compilé (résultat du builder)
COPY --from=builder /app/dist/ /var/www/html/

# S'assurer que les dossiers sessions et deployments sont accessibles en écriture
RUN mkdir -p /var/www/html/api/sessions \
    && mkdir -p /var/www/html/api/deployments \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/api/sessions \
    && chmod -R 775 /var/www/html/api/deployments

EXPOSE 80
