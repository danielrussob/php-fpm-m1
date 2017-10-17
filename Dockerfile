FROM php:5.6.31-fpm

# Installo "curl", "libmemcached-dev", "libpq-dev", "libjpeg-dev", "libpng12-dev", "libfreetype6-dev", "libssl-dev", "libmcrypt-dev",
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        curl \
        libmemcached-dev \
        libz-dev \
        libpq-dev \
        libjpeg-dev \
        libpng12-dev \
        libfreetype6-dev \
        libssl-dev \
        libmcrypt-dev \
		libmcrypt4 \
		libcurl3-dev \
		libjpeg62-turbo 
		
# Installo PHP mcrypt
RUN docker-php-ext-install mcrypt

# Installo mbstring
RUN docker-php-ext-install mbstring

# Installo PHP pdo_mysql
RUN docker-php-ext-install mysqli \
    && docker-php-ext-install pdo_mysql
	
# Installo PHP pdo_pgsql
RUN docker-php-ext-install pdo_pgsql		

# Installo PHP gd
RUN docker-php-ext-install gd && \
    docker-php-ext-configure gd \
        --enable-gd-native-ttf \
        --with-jpeg-dir=/usr/lib \
        --with-freetype-dir=/usr/include/freetype2 && \
    docker-php-ext-install gd
	
# Installo SOAP
RUN apt-get update -yqq && \
    apt-get -y install libxml2-dev php-soap && \
    docker-php-ext-install soap

# Installo Xdebug
RUN apt-get install -y php5-xdebug && \
	echo "zend_extension=/usr/lib/php5/20131226/xdebug.so" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Installo ZIP
RUN docker-php-ext-install zip

# Installo BCMATH
RUN docker-php-ext-install bcmath

# Installo Exif
RUN docker-php-ext-install exif

# Installo MySqlI
COPY ./mysql.ini /usr/local/etc/php/conf.d/mysql.ini
RUN docker-php-ext-install mysql && \
    docker-php-ext-install mysqli
	
# Installo Tokenizer
RUN docker-php-ext-install tokenizer

# Installo INTL
RUN apt-get install -y zlib1g-dev libicu-dev g++ && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl

# Installo ImageOptimizer
USER root
RUN apt-get update -yqq && \
    apt-get install -y --force-yes jpegoptim optipng pngquant gifsicle

# Installo tools
RUN apt-get update && apt-get install -y \
    mysql-client \
    vim \
    telnet \
    netcat \
    git-core \
    zip \
	openssh-client \
	openssh-server \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get purge -y --auto-remove
	
# Installo composer
RUN curl -s http://getcomposer.org/installer | php && \
    echo "export PATH=${PATH}:/var/www/vendor/bin" >> ~/.bashrc && \
    mv composer.phar /usr/local/bin/composer

RUN sed  -ibak -re "s/PermitRootLogin without-password/PermitRootLogin yes/g" /etc/ssh/sshd_config
RUN echo "root:root" | chpasswd

RUN systemctl enable ssh
#
#--------------------------------------------------------------------------
# Final Touch
#--------------------------------------------------------------------------
#

RUN mkdir /var/www/sites-available
RUN mkdir /var/www/logs
RUN mkdir /var/www/dumps

RUN usermod -u 1000 www-data

WORKDIR /var/www

#ENTRYPOINT service ssh restart && bash
#CMD ["php-fpm"]
CMD service ssh restart && php-fpm
ADD init.sh /var/www/init.sh
ADD set-magento.php /var/www/set-magento.php
ADD magento.conf.tpl /var/www/magento.conf.tpl

EXPOSE 9000