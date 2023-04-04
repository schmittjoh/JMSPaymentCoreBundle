ARG PHP_VERSION="7.4"
ARG PHP_BASE_IMAGE="eu.gcr.io/gsoi-build/docker-base-images-php-${PHP_VERSION}:latest"

# -------
# DEFAULT
# -------
FROM ${PHP_BASE_IMAGE} AS app
ARG OPCACHE_ENABLED=0
ARG XDEBUG_ENABLED=1
ARG XDEBUG_MODE=develop,coverage,debug,trace