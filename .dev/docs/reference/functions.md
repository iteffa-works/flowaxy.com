# Глобальные функции Flowaxy CMS

Глобальные функции-хелперы для упрощенного доступа к компонентам системы.

## Функции доступа к сервисам

### database()

Получение экземпляра Database:

```php
$db = database();
$stmt = $db->query("SELECT * FROM users");
```

### cache()

Получение экземпляра Cache:

```php
$cache = cache();
$data = $cache->get('key', 'default');
$cache->set('key', 'value', 3600);
```

### logger()

Получение экземпляра Logger:

```php
$logger = logger();
$logger->logInfo('Сообщение');
$logger->logError('Ошибка', ['context' => $data]);
```

### settingsManager()

Получение экземпляра SettingsManager:

```php
$settings = settingsManager();
$value = $settings->get('setting_key', 'default');
$settings->set('setting_key', 'value');
```

### cookieManager()

Получение экземпляра CookieManager:

```php
$cookie = cookieManager();
$cookie->set('key', 'value');
$value = $cookie->get('key', 'default');
```

### sessionManager()

Получение экземпляра SessionManager:

```php
$session = sessionManager();
$session->set('key', 'value');
$value = $session->get('key', 'default');
```

### storageManager()

Получение экземпляра StorageManager:

```php
$storage = storageManager();
$storage->set('key', 'value', 'local');
$value = $storage->get('key', 'default', 'local');
```

## Функции работы с хуками

### addHook()

Регистрация хука:

```php
addHook(string $hookName, callable $callback, int $priority = 10): void
```

### addFilter()

Регистрация фильтра:

```php
addFilter(string $hookName, callable $callback, int $priority = 10): void
```

### addAction()

Регистрация события:

```php
addAction(string $hookName, callable $callback, int $priority = 10): void
```

### applyFilter()

Применение фильтра:

```php
$value = applyFilter(string $hookName, $value, ...$args): mixed
```

### doAction()

Выполнение события:

```php
doAction(string $hookName, ...$args): void
```

### hasHook()

Проверка наличия хука:

```php
if (hasHook('hook_name')) {
    // Хук зарегистрирован
}
```

### removeHook()

Удаление хука:

```php
removeHook(string $hookName, callable $callback): bool
```

## Функции работы с ролями

### currentUser()

Получение текущего пользователя:

```php
$user = currentUser();
if ($user) {
    $userId = $user['id'];
    $userEmail = $user['email'];
}
```

### hasRole()

Проверка роли пользователя:

```php
if (hasRole('developer')) {
    // Пользователь имеет роль developer
}
```

### hasPermission()

Проверка права доступа:

```php
if (hasPermission('manage_plugins')) {
    // Пользователь имеет право
}
```

### can()

Проверка возможности выполнения действия:

```php
if (can('edit_post', $post)) {
    // Можно редактировать пост
}
```

## Функции работы с URL

### url()

Генерация URL:

```php
$url = url('/path'); // Относительный URL
$url = url('/path', true); // Абсолютный URL
```

### adminUrl()

Генерация URL админ-панели:

```php
$url = adminUrl('plugins'); // /admin/plugins
```

### assetUrl()

Генерация URL ресурса:

```php
$url = assetUrl('css/style.css');
```

## Функции безопасности

### csrfToken()

Генерация CSRF токена:

```php
$token = csrfToken();
```

### csrfField()

Генерация скрытого поля CSRF:

```php
echo csrfField(); // <input type="hidden" name="csrf_token" value="...">
```

### verifyCsrf()

Проверка CSRF токена:

```php
if (verifyCsrf($_POST['csrf_token'])) {
    // Токен валиден
}
```

### sanitize()

Санитизация данных:

```php
$clean = sanitize($dirty);
```

### escape()

Экранирование вывода:

```php
echo escape($userInput);
```

## Функции работы с темами

### getThemeUrl()

Получение URL темы:

```php
$themeUrl = getThemeUrl();
$cssUrl = $themeUrl . '/assets/css/style.css';
```

### getThemePath()

Получение пути к теме:

```php
$themePath = getThemePath();
$filePath = $themePath . '/templates/header.php';
```

### isThemeActive()

Проверка активности темы:

```php
if (isThemeActive('my-theme')) {
    // Тема активна
}
```

## Функции работы с плагинами

### isPluginActive()

Проверка активности плагина:

```php
if (isPluginActive('my-plugin')) {
    // Плагин активен
}
```

### getPluginUrl()

Получение URL плагина:

```php
$pluginUrl = getPluginUrl('my-plugin');
```

### getPluginPath()

Получение пути к плагину:

```php
$pluginPath = getPluginPath('my-plugin');
```

## Функции работы с конфигурацией

### config()

Получение значения конфигурации:

```php
$value = config('database.host', 'localhost');
```

### env()

Получение переменной окружения:

```php
$value = env('APP_DEBUG', false);
```

## Функции работы с данными

### db()

Получение подключения к БД (алиас для database()):

```php
$db = db();
```

### getOption()

Получение опции (алиас для settingsManager()->get()):

```php
$value = getOption('option_key', 'default');
```

### updateOption()

Обновление опции (алиас для settingsManager()->set()):

```php
updateOption('option_key', 'value');
```

## Функции работы с файлами

### uploadsUrl()

Получение URL загрузок:

```php
$url = uploadsUrl('image.jpg');
```

### uploadsPath()

Получение пути к загрузкам:

```php
$path = uploadsPath('image.jpg');
```

## Функции форматирования

### formatDate()

Форматирование даты:

```php
$formatted = formatDate($date, 'd.m.Y H:i');
```

### formatBytes()

Форматирование размера файла:

```php
$size = formatBytes(1024); // "1 KB"
```

## Полный список функций

Все функции определены в:
- `engine/includes/functions.php` - основные функции
- `engine/includes/role-functions.php` - функции работы с ролями

