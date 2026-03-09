### Multi-step building ###

# 1-step) BUILD IMAGE AS IS
FROM php:8.5.3-zts-alpine3.22 AS dry_build

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY . /app


# 2-step) BUILD SAFE IMAGE using dry build
FROM php:8.5.3-zts-alpine3.22

WORKDIR /app

RUN addgroup -S app && \
    adduser -S -G app -h /app app

COPY --from=dry_build /usr/local/lib/php/extensions \
                      /usr/local/lib/php/extensions

COPY --from=dry_build /usr/local/etc/php/conf.d \
                      /usr/local/etc/php/conf.d

COPY --from=dry_build /app .

USER app

EXPOSE 80

ENTRYPOINT ["php"]

CMD ["-S", "0.0.0.0:80"]