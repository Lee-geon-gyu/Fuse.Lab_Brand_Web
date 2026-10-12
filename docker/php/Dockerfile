# 카페24 10G 광아우토반 FullSSD+ 에서 선택 가능한 PHP 8.2 와 맞춤
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
 && a2enmod rewrite headers

# 카페24처럼 .htaccess 사용 허용
RUN sed -ri 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf

COPY php.ini /usr/local/etc/php/conf.d/zz-dev.ini

WORKDIR /var/www/html
