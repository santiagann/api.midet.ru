#!/usr/bin/bash
git clone git@github.com:santiagann/api.celfin.ru.git /var/www/celfin.ru/api/
chown -R www-data:www-data /var/www/celfin.ru/api
cd /var/www/celfin.ru/api
sudo -u www-data composer install
mv -r /var/www/celfin.ru/api/install_script ~/
