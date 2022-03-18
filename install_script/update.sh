#!/usr/bin/bash
cd /var/www/celfin.ru/api
git pull
chown -R www-data:www-data /var/www/celfin.ru/api
sudo -u www-data composer update
