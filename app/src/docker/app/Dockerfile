FROM php:8.5.3-zts-alpine3.22

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

WORKDIR /app

COPY . .

CMD ["php", "-S", "0.0.0.0:80"]