FROM php:7.4-cli

###
### Envs
###
ENV MY_USER="app" \
	MY_GROUP="app"

ARG DOCKER_UID=1000
ARG DOCKER_GID=1000

###
### User/Group
###
RUN set -eux \
	&& groupadd -g ${DOCKER_GID} -r ${MY_GROUP} \
	&& useradd -d /home/${MY_USER} -u ${DOCKER_UID} -m -s /bin/bash -g ${MY_GROUP} ${MY_USER}


RUN apt-get update && apt-get install -y git unzip


ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_MEMORY_LIMIT -1
ENV COMPOSER_CACHE_DIR /home/app/.composer_cache
ENV COMPOSER_HOME /home/app/.composer

RUN mkdir -p ${COMPOSER_CACHE_DIR} && chown -R ${DOCKER_UID}:${DOCKER_GID} ${COMPOSER_CACHE_DIR}
RUN mkdir -p ${COMPOSER_HOME} && chown -R ${DOCKER_UID}:${DOCKER_GID} ${COMPOSER_HOME}


RUN apt-get install -y git unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
USER app

#boost composer
RUN composer -vvv global require hirak/prestissimo
RUN composer -vvv global require pyrech/composer-changelogs
USER root

# -------------------- Installing PHP Extension: xdebug --------------------
RUN set -eux \
    # Installation: Generic
    # Type:         PECL extension
    # Default:      Pecl command
    && pecl install xdebug-2.9.6 \
    && docker-php-ext-enable xdebug \
    && true

WORKDIR /app
