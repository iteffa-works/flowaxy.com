# Установка и настройка WSL2 для веб-разработки

**PHP 8.4 + MySQL/MariaDB + Apache + .htaccess на Windows 11**

---

## Содержание

1. [Включение WSL2](#1-включение-wsl2)
2. [Настройка Ubuntu](#2-настройка-ubuntu)
3. [Установка PHP 8.4](#3-установка-php-84)
4. [Установка MySQL/MariaDB](#4-установка-mysqlmariadb)
5. [Установка Apache с поддержкой .htaccess](#5-установка-apache-с-поддержкой-htaccess)
6. [Настройка виртуального хоста](#6-настройка-виртуального-хоста)
7. [Работа с проектом в Windows](#7-работа-с-проектом-в-windows)
8. [Подключение VS Code](#8-подключение-vs-code)
9. [Автозапуск сервисов](#9-автозапуск-сервисов)
10. [Настройка SSL с Let's Encrypt](#10-настройка-ssl-с-lets-encrypt)
11. [Полезные команды](#11-полезные-команды)

---

## 1. Включение WSL2

### Шаг 1: Установка WSL2

Откройте **PowerShell от имени администратора** и выполните:

```powershell
wsl --install
```

Эта команда установит:
- WSL2
- Ubuntu (по умолчанию)
- Виртуальную машину (Virtual Machine Platform)

### Шаг 2: Перезагрузка

После установки **обязательно перезагрузите компьютер**.

### Шаг 3: Первый запуск

После перезагрузки откройте Ubuntu из меню Пуск. При первом запуске:
1. Введите имя пользователя (латиницей, без пробелов)
2. Введите пароль (символы не будут отображаться при вводе)
3. Подтвердите пароль

### Проверка версии WSL

```bash
wsl --version
```

Должна быть версия 2.x или выше.

---

## 2. Настройка Ubuntu

### Обновление системы

```bash
sudo apt update && sudo apt upgrade -y
```

### Установка необходимых утилит

```bash
sudo apt install -y curl wget git unzip nano
```

---

## 3. Установка PHP 8.4

### Шаг 1: Добавление репозитория PHP

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### Шаг 2: Установка PHP и расширений

```bash
sudo apt install -y php8.4 \
    php8.4-fpm \
    php8.4-cli \
    php8.4-mysql \
    php8.4-xml \
    php8.4-curl \
    php8.4-zip \
    php8.4-gd \
    php8.4-intl \
    php8.4-bcmath \
    php8.4-mbstring \
    php8.4-opcache
```

### Шаг 3: Проверка установки

```bash
php -v
```

Должна отобразиться версия PHP 8.4.x

### Шаг 4: Настройка PHP для Apache

Убедитесь, что модуль PHP включен:

```bash
sudo a2enmod php8.4
sudo systemctl restart apache2
```

### Шаг 5: Настройка php.ini (опционально)

Откройте конфигурацию PHP:

```bash
sudo nano /etc/php/8.4/apache2/php.ini
```

Основные настройки (найдите и раскомментируйте/измените):

```ini
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
date.timezone = Europe/Kiev
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

После изменения:

```bash
sudo systemctl restart apache2
```

---

## 4. Установка MySQL/MariaDB

### Вариант A: MySQL

```bash
sudo apt install mysql-server -y
sudo mysql_secure_installation
```

При настройке безопасности:
- Настройте пароль для root (если запросит)
- Удалите анонимных пользователей: `Yes`
- Отключите удаленный вход root: `Yes`
- Удалите тестовую БД: `Yes`
- Перезагрузите таблицу привилегий: `Yes`

### Вариант B: MariaDB

```bash
sudo apt install mariadb-server mariadb-client -y
sudo mysql_secure_installation
```

### Настройка доступа к MySQL

По умолчанию MySQL в WSL требует sudo. Для удобства создайте пользователя:

```bash
sudo mysql
```

В MySQL консоли:

```sql
CREATE USER 'your_username'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON *.* TO 'your_username'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

Теперь можно подключаться без sudo:

```bash
mysql -u your_username -p
```

### Проверка работы MySQL

```bash
sudo systemctl status mysql
# или для MariaDB
sudo systemctl status mariadb
```

---

## 5. Установка Apache с поддержкой .htaccess

### Шаг 1: Установка Apache

```bash
sudo apt install apache2 libapache2-mod-php8.4 -y
```

### Шаг 2: Включение необходимых модулей

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo a2enmod deflate
```

### Шаг 3: Настройка AllowOverride

Откройте конфигурацию Apache:

```bash
sudo nano /etc/apache2/apache2.conf
```

Найдите блок:

```apache
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

Измените на:

```apache
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

**Если ваш проект в Windows**, добавьте отдельный блок:

```apache
<Directory /mnt/c/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Или для конкретной папки:

```apache
<Directory /mnt/c/projects>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Шаг 4: Перезапуск Apache

```bash
sudo systemctl restart apache2
```

### Шаг 5: Проверка работы Apache

Откройте в браузере: `http://localhost`

Должна отобразиться страница "Apache2 Ubuntu Default Page"

---

## 6. Настройка виртуального хоста

### Шаг 1: Создание конфигурации сайта

```bash
sudo nano /etc/apache2/sites-available/flowaxy.conf
```

### Шаг 2: Конфигурация для локальной разработки

Вставьте следующую конфигурацию (замените пути на свои):

```apache
<VirtualHost *:80>
    ServerName flowaxy.local
    ServerAlias www.flowaxy.local
    DocumentRoot /mnt/d/OSPanel/home/flowaxy.com
    
    <Directory /mnt/d/OSPanel/home/flowaxy.com>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/flowaxy_error.log
    CustomLog ${APACHE_LOG_DIR}/flowaxy_access.log combined
    
    # Для PHP
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
</VirtualHost>
```

### Шаг 3: Активация сайта

```bash
sudo a2ensite flowaxy.conf
sudo systemctl reload apache2
```

### Шаг 4: Отключение дефолтного сайта (опционально)

```bash
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

### Шаг 5: Настройка hosts в Windows

Откройте **Notepad от имени администратора** и откройте файл:

```
C:\Windows\System32\drivers\etc\hosts
```

Добавьте строку:

```
127.0.0.1    flowaxy.local
127.0.0.1    www.flowaxy.local
```

Сохраните файл.

### Шаг 6: Проверка

Откройте в браузере: `http://flowaxy.local`

---

## 7. Работа с проектом в Windows

### Пути файлов

- **В Windows**: `D:\OSPanel\home\flowaxy.com`
- **В WSL**: `/mnt/d/OSPanel/home/flowaxy.com`

### Важные замечания

⚠️ **Внимание**: Файлы на дисках Windows (`/mnt/`) могут работать медленнее. Для лучшей производительности:

1. **Перенесите проект в WSL** (рекомендуется):

```bash
# Создайте папку в домашней директории WSL
mkdir -p ~/projects
mv /mnt/d/OSPanel/home/flowaxy.com ~/projects/
```

2. **Или используйте WSL2 файловую систему** для кэша/временных файлов:

```bash
# Создайте симлинк для кэша
ln -s ~/cache /mnt/d/OSPanel/home/flowaxy.com/cache
```

### Настройка прав доступа

Для работы `.htaccess` и записи файлов:

```bash
# Дайте права на запись для папок, которые требуют записи
sudo chmod -R 775 /mnt/d/OSPanel/home/flowaxy.com/cache
sudo chmod -R 775 /mnt/d/OSPanel/home/flowaxy.com/uploads
```

---

## 8. Подключение VS Code

### Шаг 1: Установка расширения

В VS Code установите расширение: **Remote - WSL**

### Шаг 2: Открытие проекта в WSL

1. Откройте терминал WSL
2. Перейдите в папку проекта:

```bash
cd /mnt/d/OSPanel/home/flowaxy.com
```

3. Откройте в VS Code:

```bash
code .
```

VS Code автоматически подключится к WSL и все расширения будут работать в контексте WSL.

### Полезные расширения для PHP

- PHP Intelephense
- PHP Debug
- PHP DocBlocker
- GitLens

---

## 9. Автозапуск сервисов

### Метод 1: Через wsl.conf (рекомендуется)

Создайте/отредактируйте файл:

```bash
sudo nano /etc/wsl.conf
```

Добавьте:

```ini
[boot]
command="service mysql start && service apache2 start"
```

После изменения закройте все терминалы WSL и выполните в PowerShell:

```powershell
wsl --shutdown
```

При следующем запуске WSL сервисы запустятся автоматически.

### Метод 2: Через systemd (альтернатива)

В более новых версиях WSL можно использовать systemd:

```bash
sudo nano /etc/wsl.conf
```

Добавьте:

```ini
[boot]
systemd=true
```

После этого можно управлять сервисами через systemctl:

```bash
sudo systemctl enable mysql
sudo systemctl enable apache2
```

---

## 10. Настройка SSL с Let's Encrypt

> ⚠️ **Примечание**: SSL с Let's Encrypt работает только с реальными доменами, доступными из интернета.

### Шаг 1: Установка Certbot

```bash
sudo apt install certbot python3-certbot-apache -y
```

### Шаг 2: Настройка виртуального хоста

Убедитесь, что виртуальный хост настроен с реальным доменом:

```bash
sudo nano /etc/apache2/sites-available/flowaxy.conf
```

Пример:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /mnt/d/OSPanel/home/flowaxy.com
    
    <Directory /mnt/d/OSPanel/home/flowaxy.com>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Активируйте и перезагрузите:

```bash
sudo a2ensite flowaxy.conf
sudo systemctl reload apache2
```

### Шаг 3: Получение SSL сертификата

```bash
sudo certbot --apache
```

Certbot попросит:
- **Домен**: укажите ваш реальный домен
- **Email**: введите email для уведомлений
- **Согласие с Terms**: введите `Yes`
- **Редирект HTTP → HTTPS**: выберите `2` (Redirect) - рекомендуется

### Шаг 4: Автоматическое обновление

Проверьте, что таймер активен:

```bash
sudo systemctl status certbot.timer
```

Проверка обновления вручную:

```bash
sudo certbot renew --dry-run
```

### Шаг 5: Настройка Windows Firewall (если домен на ваш ПК)

Откройте PowerShell от имени администратора:

```powershell
netsh advfirewall firewall add rule name="Apache HTTPS" dir=in action=allow protocol=TCP localport=443
netsh advfirewall firewall add rule name="Apache HTTP" dir=in action=allow protocol=TCP localport=80
```

### Шаг 6: Проверка SSL

Откройте: `https://yourdomain.com`

---

## 11. Полезные команды

### WSL

```bash
# Перезапуск WSL (из PowerShell)
wsl --shutdown

# Получить IP адрес WSL
hostname -I

# Список установленных дистрибутивов
wsl --list --verbose

# Запуск конкретного дистрибутива
wsl -d Ubuntu
```

### Apache

```bash
# Статус Apache
sudo systemctl status apache2

# Запуск Apache
sudo systemctl start apache2

# Остановка Apache
sudo systemctl stop apache2

# Перезапуск Apache
sudo systemctl restart apache2

# Перезагрузка конфигурации (без перезапуска)
sudo systemctl reload apache2

# Проверка конфигурации Apache
sudo apache2ctl configtest

# Просмотр ошибок
sudo tail -f /var/log/apache2/error.log

# Просмотр логов конкретного сайта
sudo tail -f /var/log/apache2/flowaxy_error.log
```

### MySQL/MariaDB

```bash
# Статус MySQL
sudo systemctl status mysql

# Запуск MySQL
sudo systemctl start mysql

# Остановка MySQL
sudo systemctl stop mysql

# Перезапуск MySQL
sudo systemctl restart mysql

# Подключение к MySQL
mysql -u root -p

# Создание базы данных
mysql -u root -p -e "CREATE DATABASE flowaxy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Импорт базы данных
mysql -u root -p flowaxy < /path/to/dump.sql

# Экспорт базы данных
mysqldump -u root -p flowaxy > /path/to/backup.sql
```

### PHP

```bash
# Версия PHP
php -v

# Конфигурация PHP
php -i

# Запуск встроенного сервера (для тестирования)
php -S localhost:8000

# Проверка установленных модулей PHP
php -m

# Путь к php.ini
php --ini
```

### Файлы и права доступа

```bash
# Изменить владельца
sudo chown -R $USER:$USER /path/to/directory

# Изменить права доступа
sudo chmod -R 755 /path/to/directory

# Для папок с записью
sudo chmod -R 775 /path/to/directory

# Рекурсивное изменение прав
sudo find /path/to/directory -type d -exec chmod 755 {} \;
sudo find /path/to/directory -type f -exec chmod 644 {} \;
```

---

## Решение проблем

### Проблема: Apache не запускается

```bash
# Проверьте конфигурацию
sudo apache2ctl configtest

# Проверьте логи
sudo tail -f /var/log/apache2/error.log

# Проверьте, не занят ли порт 80
sudo netstat -tulpn | grep :80
```

### Проблема: .htaccess не работает

1. Убедитесь, что `mod_rewrite` включен: `sudo a2enmod rewrite`
2. Проверьте `AllowOverride All` в конфигурации
3. Перезапустите Apache: `sudo systemctl restart apache2`

### Проблема: PHP файлы загружаются как текст

```bash
# Убедитесь, что модуль PHP включен
sudo a2enmod php8.4

# Проверьте, что в конфигурации виртуального хоста есть:
<FilesMatch \.php$>
    SetHandler application/x-httpd-php
</FilesMatch>
```

### Проблема: Медленная работа файлов из Windows

Рассмотрите возможность переноса проекта в файловую систему WSL (`~/projects`) для лучшей производительности.

### Проблема: Не могу подключиться к MySQL

```bash
# Проверьте статус MySQL
sudo systemctl status mysql

# Перезапустите MySQL
sudo systemctl restart mysql

# Попробуйте подключиться с sudo
sudo mysql -u root
```

---

## Дополнительные настройки

### Увеличение лимитов PHP для больших файлов

Отредактируйте `/etc/php/8.4/apache2/php.ini`:

```ini
upload_max_filesize = 256M
post_max_size = 256M
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
```

### Настройка кэширования OPcache

В том же файле:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### Оптимизация Apache для производительности

Отредактируйте `/etc/apache2/apache2.conf`:

```apache
# Увеличьте лимиты
ServerLimit 16
MaxRequestWorkers 400
```

---

## Полезные ссылки

- [Документация WSL](https://docs.microsoft.com/en-us/windows/wsl/)
- [Документация Apache](https://httpd.apache.org/docs/)
- [Документация PHP](https://www.php.net/docs.php)
- [Документация Certbot](https://certbot.eff.org/)

---

**Готово!** Теперь у вас настроена полноценная среда разработки на WSL2. 🚀
