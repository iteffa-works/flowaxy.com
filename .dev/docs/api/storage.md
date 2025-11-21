# Система хранилищ

Flowaxy CMS предоставляет единый интерфейс для работы с различными типами хранилищ данных.

## Введение

Система хранилищ позволяет работать с:
- **Cookies** - через `CookieManager`
- **Sessions** - через `SessionManager`
- **Client Storage** - через `StorageManager` (localStorage/sessionStorage)

Все менеджеры реализуют единый интерфейс `StorageInterface`, что обеспечивает консистентный API.

## CookieManager

Управление cookies на стороне сервера.

### Получение экземпляра

```php
$cookieManager = cookieManager();
```

### Основные методы

#### `get(string $key, $default = null)`
Получение значения cookie.

```php
$value = $cookieManager->get('user_preference', 'default');
```

#### `set(string $key, $value, array $options = []): bool`
Установка значения cookie.

```php
$cookieManager->set('user_preference', 'value', [
    'expire' => '+30 days',
    'path' => '/',
    'domain' => '.example.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

#### `delete(string $key): bool`
Удаление cookie.

```php
$cookieManager->delete('user_preference');
```

#### `has(string $key): bool`
Проверка наличия cookie.

```php
if ($cookieManager->has('user_preference')) {
    // ...
}
```

### Опции cookie

- `expire` - время истечения (строка типа `+30 days` или timestamp)
- `path` - путь (по умолчанию `/`)
- `domain` - домен
- `secure` - только HTTPS (по умолчанию `false`)
- `httponly` - только HTTP (по умолчанию `true`)
- `samesite` - SameSite атрибут (`Strict`, `Lax`, `None`)

---

## SessionManager

Управление сессиями на стороне сервера.

### Получение экземпляра

```php
$sessionManager = sessionManager();
```

### Основные методы

#### `get(string $key, $default = null)`
Получение значения из сессии.

```php
$userId = $sessionManager->get('user_id');
```

#### `set(string $key, $value): bool`
Установка значения в сессию.

```php
$sessionManager->set('user_id', 123);
```

#### `delete(string $key): bool`
Удаление значения из сессии.

```php
$sessionManager->delete('user_id');
```

#### `has(string $key): bool`
Проверка наличия значения.

```php
if ($sessionManager->has('user_id')) {
    // ...
}
```

#### `clear(): bool`
Очистка всей сессии.

```php
$sessionManager->clear();
```

#### `regenerate(): bool`
Регенерация ID сессии.

```php
$sessionManager->regenerate();
```

---

## StorageManager

Управление клиентским хранилищем (localStorage/sessionStorage).

### Получение экземпляра

```php
$storageManager = storageManager();
```

### Основные методы

#### `get(string $key, $default = null)`
Получение значения из хранилища.

```php
$value = $storageManager->get('preference', 'default');
```

#### `set(string $key, $value, string $type = 'local'): bool`
Установка значения в хранилище.

```php
$storageManager->set('preference', 'value', 'local'); // localStorage
$storageManager->set('preference', 'value', 'session'); // sessionStorage
```

#### `delete(string $key, string $type = 'local'): bool`
Удаление значения из хранилища.

```php
$storageManager->delete('preference', 'local');
```

#### `has(string $key, string $type = 'local'): bool`
Проверка наличия значения.

```php
if ($storageManager->has('preference', 'local')) {
    // ...
}
```

#### `clear(string $type = 'local'): bool`
Очистка хранилища.

```php
$storageManager->clear('local');
```

### Типы хранилища

- `local` - localStorage (постоянное хранилище)
- `session` - sessionStorage (хранилище на время сессии)

### JavaScript API

StorageManager также предоставляет JavaScript API:

```javascript
// Получение значения
const value = StorageManager.get('key', 'default', 'local');

// Установка значения
StorageManager.set('key', 'value', 'local');

// Удаление значения
StorageManager.delete('key', 'local');

// Проверка наличия
if (StorageManager.has('key', 'local')) {
    // ...
}

// Очистка
StorageManager.clear('local');
```

---

## StorageFactory

Фабрика для создания менеджеров хранилища.

### Получение менеджера

```php
$cookieManager = StorageFactory::create('cookie');
$sessionManager = StorageFactory::create('session');
$storageManager = StorageFactory::create('storage');
```

### Доступные типы

- `cookie` - CookieManager
- `session` - SessionManager
- `storage` - StorageManager

---

## Примеры использования

### Пример 1: Сохранение предпочтений пользователя

```php
// Сохранение в cookie
cookieManager()->set('theme', 'dark', [
    'expire' => '+365 days',
    'httponly' => false // Доступно из JavaScript
]);

// Получение предпочтения
$theme = cookieManager()->get('theme', 'light');
```

### Пример 2: Работа с сессией

```php
// Сохранение данных пользователя
sessionManager()->set('user_id', $userId);
sessionManager()->set('user_role', $userRole);

// Получение данных
$userId = sessionManager()->get('user_id');
$userRole = sessionManager()->get('user_role');

// Очистка при выходе
sessionManager()->clear();
```

### Пример 3: Клиентское хранилище

```php
// Сохранение на клиенте
storageManager()->set('draft_content', $content, 'local');

// В JavaScript
StorageManager.set('draft_content', content, 'local');
const draft = StorageManager.get('draft_content', '', 'local');
```

---

## Рекомендации

1. **Cookies** - для небольших данных, которые нужны на сервере
2. **Sessions** - для временных данных пользователя
3. **Storage** - для данных, которые нужны только на клиенте

## Безопасность

- Всегда используйте `httponly` для чувствительных cookies
- Используйте `secure` для cookies в продакшене (HTTPS)
- Не храните чувствительные данные в localStorage
- Регенерируйте ID сессии при повышении привилегий

---

**Подробная документация:** [STORAGE_MANAGERS.md](../STORAGE_MANAGERS.md)

