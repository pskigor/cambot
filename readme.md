Установка базовых пакетов
```shell
sudo apt install curl wget ffmpeg git php php-curl php-memcached php-mysql php-pgsql php-gd php-imagick php-intl php-xml php-zip php-mbstring
```

Создаём папку для бота и выставляем права
```shell
mkdir /tgbot
mkdir /tgbot/cam
chmod -R ugo+rwx /tgbot
chmod -R ugo+rwx /tgbot/cam
```

```shell
cd /tgbot
```

Размещаем в папке tgbot файлы index.php и updater.sh

Правим скрипт updater для работы со всеми камерами.
Для камеры необходимо добавить:
```shell
ffmpeg -y -i "ССЫЛКА НА ПОТОК" -vframes 1 /tgbot/cam/camX.jpg
chmod ugo+rwx /tgbot/cam/camX.jpg
```
X - номер камеры

Пример:
```shell
ffmpeg -y -i "rtsp://admin:admin@192.168.0.10:554" -vframes 1 /tgbot/cam/cam1.jpg
chmod ugo+rwx /tgbot/cam/cam1.jpg
```

Делаем скрипт исполняемым
```shell
chmod ugo+x updater.sh
```

Тестово запускаем скрипт и проверяем наличие файлов в /tgbot/cam
```shell
./updater.sh
```

Указываем в cron автоматизацию
```
* * * * * php /tgbot/index.php
* * * * * sleep 20; php /tgbot/index.php
* * * * * sleep 40; php /tgbot/index.php
* * * * * /tgbot/updater.sh
```
