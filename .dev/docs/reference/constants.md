# Константы Flowaxy CMS

Все константы системы определены в `engine/flowaxy.php`.

## Основные константы

### Директории

```php
ROOT_DIR          // Корневая директория проекта
ENGINE_DIR        // Директория движка (engine/)
UPLOADS_DIR       // Директория загрузок (uploads/)
UPLOADS_URL       // URL загрузок
CACHE_DIR         // Директория кеша (storage/cache/)
LOGS_DIR          // Директория логов (storage/logs/)
```

### URL

```php
SITE_URL          // URL сайта
ADMIN_URL         // URL админ-панели
```

### Сессии и безопасность

```php
ADMIN_SESSION_NAME    // Имя сессии админки ('cms_admin_logged_in')
CSRF_TOKEN_NAME       // Имя CSRF токена ('csrf_token')
```

### Пароли

```php
PASSWORD_MIN_LENGTH   // Минимальная длина пароля (8, загружается из SystemConfig)
```

## Использование

```php
// Получение пути к файлу
$filePath = ROOT_DIR . '/uploads/image.jpg';

// Получение URL
$imageUrl = UPLOADS_URL . '/image.jpg';

// Работа с кешем
$cacheFile = CACHE_DIR . 'data.cache';

// Работа с логами
$logFile = LOGS_DIR . 'app-' . date('Y-m-d') . '.log';
```

## Проверка констант

```php
if (defined('SITE_URL')) {
    // Константа определена
}

// Значение по умолчанию
$url = defined('SITE_URL') ? SITE_URL : 'http://localhost';
```

