FROM php:8.2-fpm

COPY . /app

RUN apt-get update && apt-get install -y \
        git \
        nginx \
        supervisor \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libtidy-dev \
        libzip-dev \
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install -j$(nproc) gd pdo_mysql exif tidy zip \
        && curl -sS https://getcomposer.org/installer \
         | php -- --install-dir=/usr/local/bin --filename=composer --version=2.6.5 \
        && chmod +x /usr/local/bin/composer \
        && cp -R /app/docker/nginx/* /etc/nginx/ \
        && cp /app/docker/php/app.ini /usr/local/etc/php/conf.d/php.ini \
        && cp /app/docker/supervisor/supervisor.conf /etc/supervisor/conf.d/supervisor.conf \
        && cp /app/docker/provision/entrypoint.sh /entrypoint.sh

RUN bash /app/docker/provision/after-build.sh

EXPOSE 80 9000

WORKDIR /app

ENTRYPOINT ["/entrypoint.sh"]

CMD ["/usr/bin/supervisord"]
