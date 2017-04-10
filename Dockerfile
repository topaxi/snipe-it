FROM debian:jessie-slim
MAINTAINER Brady Wetherington <uberbrady@gmail.com>

RUN apt-get update && apt-get install -y \
apache2-bin \
libapache2-mod-php5 \
php5-curl \
php5-ldap \
php5-mysql \
php5-mcrypt \
php5-gd \
patch \
curl \
vim \
git \
mysql-client \
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN php5enmod mcrypt
RUN php5enmod gd

RUN sed -i 's/variables_order = .*/variables_order = "EGPCS"/' /etc/php5/apache2/php.ini
RUN sed -i 's/variables_order = .*/variables_order = "EGPCS"/' /etc/php5/cli/php.ini

RUN useradd --uid 1000 --gid 50 docker

RUN echo export APACHE_RUN_USER=docker >> /etc/apache2/envvars
RUN echo export APACHE_RUN_GROUP=staff >> /etc/apache2/envvars

RUN sed -i "s/Listen 80/Listen 8080/" /etc/apache2/ports.conf

COPY docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf

#SSL
#RUN mkdir -p /var/lib/snipeit/ssl
#COPY docker/001-default-ssl.conf /etc/apache2/sites-enabled/001-default-ssl.conf
#COPY docker/001-default-ssl.conf /etc/apache2/sites-available/001-default-ssl.conf

#RUN a2enmod ssl
#RUN a2ensite 001-default-ssl.conf

RUN a2enmod rewrite

############## DEPENDENCIES via COMPOSER ###################

WORKDIR /var/www/html

COPY composer.json composer.lock database /var/www/html/

#global install of composer
RUN cd /tmp;curl -sS https://getcomposer.org/installer | php;mv /tmp/composer.phar /usr/local/bin/composer

# Get dependencies
RUN cd /var/www/html;composer install

############ INITIAL APPLICATION SETUP #####################

#Append to bootstrap file (less brittle than 'patch')
# RUN sed -i 's/return $app;/$env="production";\nreturn $app;/' bootstrap/start.php

#copy all configuration files
# COPY docker/*.php /var/www/html/app/config/production/
COPY docker/docker.env /var/www/html/.env

COPY . /var/www/html

RUN chown -R docker /var/www/html

RUN \
	rm -r "/var/www/html/storage/private_uploads" && ln -fs "/var/lib/snipeit/data/private_uploads" "/var/www/html/storage/private_uploads" \
      && rm -rf "/var/www/html/public/uploads" && ln -fs "/var/lib/snipeit/data/uploads" "/var/www/html/public/uploads" \
      && rm -r "/var/www/html/storage/app/backups" && ln -fs "/var/lib/snipeit/dumps" "/var/www/html/storage/app/backups"

############### APPLICATION INSTALL/INIT #################

#RUN php artisan app:install
# too interactive! Try something else

#COPY docker/app_install.exp /tmp/app_install.exp
#RUN chmod +x /tmp/app_install.exp
#RUN /tmp/app_install.exp

############### DATA VOLUME #################

RUN mkdir -p /var/lib/snipeit && \
    chown 1000:1000 -R /var/lib/snipeit /var/log/apache2 && \
    chmod o+rwX -R /var/lib/snipeit /var/log/apache2 && \
    chmod o+rwX -R /etc/apache2 && \
    chmod o+rwX /etc

##### START SERVER

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER 1000

ENTRYPOINT ["/entrypoint.sh"]

EXPOSE 8080
