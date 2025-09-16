FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    libpng-dev libgif-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql zip intl gd \
    && rm -rf /var/lib/apt/lists/*


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer


RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
RUN chmod +x /usr/local/bin/symfony


RUN pecl install xdebug

RUN groupadd -g 1000 devuser && \
    useradd -u 1000 -g devuser -m devuser

USER devuser
WORKDIR /var/www/html
