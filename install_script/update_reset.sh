#!/usr/bin/bash
cd /var/www/celfin.ru/api
git fetch --all
git reset --hard origin/master
chown -R www-data:www-data /var/www/celfin.ru/api
sudo -u www-data composer update
mv /var/www/celfin.ru/api/install_script/* ~/install_script
rm -d /var/www/celfin.ru/api/install_script/

echo "Отредактируйте index.php. В строке define('ENVIRONMENT', 'developement') замените developement на production";
