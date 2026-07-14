FROM php:8.5-fpm

# Install system dependencies and librdkafka
RUN apt-get update && apt-get install -y \
    librdkafka-dev \
    build-essential \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install rdkafka PHP extension
RUN pecl install rdkafka && \
    docker-php-ext-enable rdkafka

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["php-fpm"]