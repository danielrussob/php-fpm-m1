FROM dnafactory/php-fpm-56
	
# Installo SOAP
RUN apt-get update -yqq && \
    apt-get -y install libxml2-dev php-soap && \
    docker-php-ext-install soap \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get purge -y --auto-remove

# Installo Xdebug
RUN apt-get update -yqq && apt-get install -y php5-xdebug && \
	echo "zend_extension=/usr/lib/php5/20131226/xdebug.so" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
	&& rm -rf /var/lib/apt/lists/* \
    && apt-get purge -y --auto-remove

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
RUN apt-get update -yqq && apt-get install -y zlib1g-dev libicu-dev g++ && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get purge -y --auto-remove

# Installo ImageOptimizer
USER root
RUN apt-get update -yqq && \
    apt-get install -y --force-yes jpegoptim optipng pngquant gifsicle \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get purge -y --auto-remove

#
#--------------------------------------------------------------------------
# Final Touch
#--------------------------------------------------------------------------
#

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

RUN mkdir /var/www/sites-available
RUN mkdir /var/www/logs
RUN mkdir /var/www/dumps

RUN usermod -u 1000 www-data

COPY magento.conf /var/www/sites-available/magento.conf
RUN rm /var/www/sites-available/default.conf -Rf

WORKDIR /var/www

#ENTRYPOINT service ssh restart && bash
#CMD ["php-fpm"]
CMD service ssh restart && php-fpm
ADD init.sh /var/www/init.sh
ADD set-magento.php /var/www/set-magento.php
ADD magento.conf.tpl /var/www/magento.conf.tpl

EXPOSE 9000