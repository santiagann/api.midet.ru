#!/usr/bin/bash
file="/var/www/celfin.ru/api/application/config/database.php"
cp "/var/www/celfin.ru/api/application/config/database_default.php" "/var/www/celfin.ru/api/application/config/database.php"
echo -n "Введите адрес сервера базы данных: (localhost) "
read hostname
if [ $hostname = '' ];
then
$hostname='localhost'
fi
perl -e "s/{hostname}/$hostname/g" -pi $file
echo -n "Введите имя пользователя базы данных: () "
read username
perl -e "s/{username}/$username/g" -pi $file
echo -n "Введите пароль базы данных(не используйте '): () "
read password
perl -e "s/{password}/$password/g" -pi $file
echo -n "Введите имя базы данных: () "
read database
perl -e "s/{database}/$database/g" -pi $file