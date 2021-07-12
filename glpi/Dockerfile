FROM php:7.4-apache

ENV APACHE_SERVER_NAME glpi-test.fmedia-office.com
ENV GLPI_VERSION 9.5.5
ENV FUSIONINVENTORY_VERSION 9.5.0+1.0
ENV USEDITEM_VERSION 2.4.0
ENV PDF_PLUGIN_LINK https://forge.glpi-project.org/attachments/download/2314/glpi-pdf-1.7.0.tar.gz

COPY ./useditemsexport /tmp/

RUN apt update && DEBIAN_FRONTEND=noninteractive apt install -y zlib1g-dev libpng-dev libc-client-dev libkrb5-dev libicu-dev libpcre3-dev libcurl4-openssl-dev wget jq libldap2-dev libxml2 libxml2-dev libxpm-dev libxslt1-dev libxslt1-dev libzip-dev libbz2-dev && apt clean && rm -r /var/lib/apt/lists/* && \
docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
a2enmod rewrite && \
docker-php-ext-install mysqli gd imap intl curl ldap xmlrpc exif zip bz2 && \
pecl install apcu && \
docker-php-ext-enable apcu opcache && \
wget -O /tmp/glpi.tar.gz $(curl -s https://api.github.com/repos/glpi-project/glpi/releases/tags/${GLPI_VERSION} | jq .assets[0].browser_download_url | tr -d \") && \
wget -O /tmp/fusioninventory.tar.bz2 https://github.com/fusioninventory/fusioninventory-for-glpi/releases/download/glpi${FUSIONINVENTORY_VERSION}/fusioninventory-${FUSIONINVENTORY_VERSION}.tar.bz2 && \
wget -O /tmp/useditemsexport.tar.bz2 https://github.com/pluginsGLPI/useditemsexport/releases/download/${USEDITEM_VERSION}/glpi-useditemsexport-${USEDITEM_VERSION}.tar.bz2 && \
wget -O /tmp/pdf.tar.gz ${PDF_PLUGIN_LINK} && \
tar -xzf /tmp/glpi.tar.gz -C /var/www && \
tar -xjf /tmp/fusioninventory.tar.bz2 -C /var/www/glpi/plugins && \
tar -xjf /tmp/useditemsexport.tar.bz2 -C /var/www/glpi/plugins && \
tar -xzf /tmp/pdf.tar.gz -C /var/www/glpi/plugins && \
mv /tmp/export.class.php /var/www/glpi/plugins/useditemsexport/inc/ && \
mv /tmp/ru_RU.mo /var/www/glpi/plugins/useditemsexport/locales/ && \
mv /tmp/ru_RU.po /var/www/glpi/plugins/useditemsexport/locales/ && \
rm -rf /tmp/glpi.tar.gz /var/www/html /tmp/fusioninventory.tar.bz2 /tmp/useditemsexport.tar.bz2 /tmp/pdf.tar.gz && \
mv /var/www/glpi /var/www/html && \
rm /var/www/html/install -r && \
apt remove wget jq -y && apt autoremove -y && \
chown -R www-data:www-data /var/www/html && \
echo "error_reporting = E_ALL\nerror_log = /var/log/apache2/php_errors.log\n" > /usr/local/etc/php/php.ini && \
echo "ServerName ${APACHE_SERVER_NAME}" >> /etc/apache2/apache2.conf

USER www-data
