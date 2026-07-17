FROM php:8.5-fpm

# Install system dependencies and librdkafka
RUN apt-get update && apt-get install -y --no-install-recommends \
    librdkafka-dev \
    build-essential \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install and enable rdkafka
RUN pecl install rdkafka \
    && docker-php-ext-enable rdkafka

# Fix: Use fully qualified image name for Docker Hub under Podman
COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["php-fpm"]