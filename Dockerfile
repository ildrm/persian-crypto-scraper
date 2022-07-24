FROM php:7.4-cli
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    libz-dev \
    libzip-dev \
    unzip
RUN pecl install zlib zip
RUN docker-php-ext-install zip

# if need composer to update plugin / vendor used
RUN php -r "copy('http://getcomposer.org/installer', 'composer-setup.php');" && \
php composer-setup.php --install-dir=/usr/bin --filename=composer && \
php -r "unlink('composer-setup.php');"

# copy all of the file in folder to /src
COPY . /src
WORKDIR /src

RUN composer update