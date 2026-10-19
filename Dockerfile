FROM php:8.2-apache

# Installation des extensions PostgreSQL nécessaires pour PDO / Supabase
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Activation du module rewrite d'Apache
RUN a2enmod rewrite

# Copie de tout le projet dans le dossier web d'Apache
COPY . /var/www/html/

# Exposition du port 80
EXPOSE 80
