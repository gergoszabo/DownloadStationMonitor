FROM ubuntu
RUN echo "Europe/Budapest" > /etc/timezone
RUN apt update && DEBIAN_FRONTEND=noninteractive apt install tzdata apache2 php libapache2-mod-php php-curl -y && a2enmod php7.4 && service apache2 restart && apt-get clean && rm /var/www/html/index.html
COPY . /var/www/html
EXPOSE 80
CMD apachectl -D FOREGROUND
