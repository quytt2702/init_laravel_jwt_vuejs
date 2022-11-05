FROM php:8.1-fpm

RUN apt-get update --fix-missing
RUN apt-get install -y build-essential
RUN apt-get install -y libfreetype6-dev
RUN apt-get install -y libjpeg62-turbo-dev
RUN apt-get install -y libmcrypt-dev
RUN apt-get install -y libpng-dev
RUN apt-get install -y curl
RUN apt-get install -y libcurl4
RUN apt-get install -y libcurl4-openssl-dev
RUN apt-get install -y zlib1g-dev
RUN apt-get install -y libicu-dev
RUN apt-get install -y default-mysql-client
RUN apt-get install -y libmagickwand-dev
RUN apt-get install -y unzip
RUN apt-get install -y libzip-dev
RUN apt-get install -y zip
RUN apt install -y libonig-dev
RUN apt-get install -y supervisor

# Configure php extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install php extensions
RUN docker-php-ext-install \
    bcmath \
    curl \
    exif \
    gd

RUN docker-php-ext-install \
    iconv \
    intl \
    mbstring \
    pdo \
    pdo_mysql \
    mysqli \
    xml \
    zip

RUN docker-php-ext-configure tokenizer

RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN groupadd -r nginx \
    && useradd -r -s /sbin/nologin -d /dev/null -g nginx nginx

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && \
    chmod a+x /usr/local/bin/composer && \
    echo "alias composer='php /usr/local/bin/composer'" >> ~/.bashrc

RUN curl -LsS https://phar.phpunit.de/phpunit-7.phar -o /usr/local/bin/phpunit && \
    chmod a+x /usr/local/bin/phpunit && \
    echo "alias phpunit='php /usr/local/bin/phpunit'" >> ~/.bashrc && \
    ## PHP loc
    curl -LsS https://phar.phpunit.de/phploc.phar -o /usr/local/bin/phploc && \
    chmod a+x /usr/local/bin/phploc && \
    ## PHPCS
    curl -LsS https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar -o /usr/local/bin/phpcs && \
    chmod a+x /usr/local/bin/phpcs && \
    ## PHPCBF
    curl -LsS https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar -o /usr/local/bin/phpcbf && \
    chmod a+x /usr/local/bin/phpcbf && \
    ## PHPMD
    curl -LsS https://phpmd.org/static/latest/phpmd.phar -o /usr/local/bin/phpmd && \
    chmod a+x /usr/local/bin/phpmd && \
    ## PHPCPD
    curl -LsS https://phar.phpunit.de/phpcpd.phar -o /usr/local/bin/phpcpd && \
    chmod a+x /usr/local/bin/phpcpd

## Install Node.js
RUN curl -LsS https://apt.nodesource.com/setup_12.x | bash -
RUN apt-get update
RUN apt-get install -y nodejs npm vim
RUN npm install -g multi-file-swagger
RUN apt autoremove
RUN apt-get install htop -y
SHELL ["/bin/bash", "-c", "source ~/.bashrc"]

# Start php-fpm

ADD ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf
CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf"]

EXPOSE 9000
