FROM php:7.2-apache

# COPY *.php /var/www/html/*.php
# COPY *.css /var/www/html/*.css
# COPY *.js /var/www/html/*.js

COPY index.php /var/www/html/index.php
COPY config.php /var/www/html/config.php
COPY main.css /var/www/html/main.css
# COPY bootstrap.min.css /var/www/html/bootstrap.min.css
# COPY bootstrap-grid.min.css /var/www/html/bootstrap-grid.min.css
# COPY bootstrap.min.js /var/www/html/bootstrap.min.js
COPY config.local.php /var/www/html/config.local.php
COPY php.ini "$PHP_INI_DIR/php.ini"
