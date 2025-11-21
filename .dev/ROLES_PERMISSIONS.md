# Система ролей и прав доступа

## Описание

Система ролей и прав доступа (RBAC - Role-Based Access Control) позволяет управлять доступом пользователей к различным функциям системы через роли и разрешения.

## Структура

### Таблицы базы данных

- **roles** - Роли пользователей
- **permissions** - Разрешения (права доступа)
- **role_permissions** - Связь ролей и разрешений (многие ко многим)
- **user_roles** - Связь пользователей и ролей (многие ко многим)

### Классы

- **RoleManager** - Основной класс для управления ролями и правами
- Расположен в: `engine/classes/managers/RoleManager.php`

### Функции

Все функции находятся в: `engine/includes/role-functions.php`

## Установка

Для установки системы ролей выполните SQL файл:

```sql
source engine/db/roles_permissions.sql
```

Или через админ-панель (если есть соответствующий функционал).

## Базовые роли

Система создает следующие роли по умолчанию:

1. **admin** (Администратор) - Полный доступ ко всем функциям
2. **user** (Пользователь) - Базовые права для работы с кабинетом
3. **moderator** (Модератор) - Расширенные права (можно настроить)

## Базовые разрешения

### Админка (admin.*)

- `admin.access` - Доступ к админ-панели
- `admin.plugins` - Управление плагинами
- `admin.themes` - Управление темами
- `admin.settings` - Управление настройками
- `admin.logs.view` - Просмотр логов
- `admin.users` - Управление пользователями
- `admin.roles` - Управление ролями и правами

### Кабинет (cabinet.*)

- `cabinet.access` - Доступ к кабинету
- `cabinet.profile.edit` - Редактирование профиля
- `cabinet.settings.view` - Просмотр настроек
- `cabinet.settings.edit` - Изменение настроек

## Использование

### Проверка разрешений

#### В админке

```php
// Проверка разрешения у текущего администратора
if (current_user_can('admin.plugins')) {
    // Пользователь может управлять плагинами
}
```

#### В публичной части (кабинет)

```php
// Проверка разрешения у авторизованного пользователя
if (auth_user_can('cabinet.settings.edit')) {
    // Пользователь может изменять настройки
}
```

#### Для конкретного пользователя

```php
// Проверка разрешения у пользователя по ID
if (user_can($userId, 'admin.plugins')) {
    // Пользователь может управлять плагинами
}
```

### Проверка ролей

```php
// Проверка роли у пользователя
if (user_has_role($userId, 'admin')) {
    // Пользователь является администратором
}
```

### Получение данных

```php
// Получить все роли пользователя
$roles = user_roles($userId);

// Получить все разрешения пользователя
$permissions = user_permissions($userId);
```

### Управление ролями

```php
// Назначить роль пользователю
assign_role($userId, $roleId);

// Удалить роль у пользователя
remove_role($userId, $roleId);
```

## Интеграция в плагины

### Проверка прав в плагине

```php
class MyPlugin extends BasePlugin {
    public function handlePage() {
        // Проверяем право доступа
        if (!function_exists('auth_user_can') || !auth_user_can('myplugin.access')) {
            die('Доступ заборонено');
        }
        
        // Показываем контент
    }
}
```

### Регистрация разрешений

```php
public function init(): void {
    // Регистрируем разрешения при инициализации плагина
    if (function_exists('roleManager')) {
        $roleManager = roleManager();
        
        // Создаем разрешения для плагина
        $roleManager->createPermission(
            'Доступ к плагину',
            'myplugin.access',
            'Доступ к основным функциям плагина',
            'plugin'
        );
        
        $roleManager->createPermission(
            'Управление настройками',
            'myplugin.settings',
            'Управление настройками плагина',
            'plugin'
        );
    }
}
```

### Использование в кабинете

```php
// Добавляем пункт меню с проверкой прав
cabinet_add_menu_item([
    'id' => 'my-page',
    'text' => 'Моя сторінка',
    'icon' => 'fas fa-star',
    'url' => '/cabinet/my-page',
    'permission' => 'myplugin.access', // Требуемое разрешение
    'order' => 20
]);

// Добавляем страницу с проверкой прав
cabinet_add_page('my-page', function($page, $subPage, $user) {
    $userId = $user['id'] ?? null;
    
    // Проверяем право доступа
    if (!$userId || !user_can($userId, 'myplugin.access')) {
        echo '<p>Доступ заборонено</p>';
        return;
    }
    
    echo '<h2>Моя сторінка</h2>';
    echo '<p>Контент доступен только пользователям с правом myplugin.access</p>';
});
```

## API RoleManager

### Основные методы

```php
$roleManager = roleManager();

// Проверка разрешения
$roleManager->hasPermission($userId, 'admin.plugins');

// Проверка роли
$roleManager->hasRole($userId, 'admin');

// Получение разрешений пользователя
$roleManager->getUserPermissions($userId);

// Получение ролей пользователя
$roleManager->getUserRoles($userId);

// Назначение роли
$roleManager->assignRole($userId, $roleId);

// Удаление роли
$roleManager->removeRole($userId, $roleId);

// Получение всех ролей
$roleManager->getAllRoles();

// Получение всех разрешений
$roleManager->getAllPermissions();
$roleManager->getAllPermissions('admin'); // По категории

// Создание роли
$roleManager->createRole('Название', 'slug', 'Описание', false);

// Создание разрешения
$roleManager->createPermission('Название', 'slug', 'Описание', 'category');

// Назначение разрешения роли
$roleManager->assignPermissionToRole($roleId, $permissionId);

// Удаление разрешения у роли
$roleManager->removePermissionFromRole($roleId, $permissionId);

// Получение разрешений роли
$roleManager->getRolePermissions($roleId);

// Удаление роли
$roleManager->deleteRole($roleId);

// Очистка кеша
$roleManager->clearUserCache($userId);
$roleManager->clearRoleCache($roleId);
```

## Интеграция в админке

Система автоматически проверяет право `admin.access` при входе в админ-панель. Если у пользователя нет этого права, доступ будет запрещен.

## Интеграция в кабинете

Плагин Cabinet автоматически проверяет право `cabinet.access` при доступе к кабинету. Пункты меню кабинета могут иметь поле `permission` для проверки прав доступа.

## Примеры использования

### Пример 1: Ограничение доступа к странице

```php
public function handlePage() {
    $user = auth_get_current_user();
    if (!$user || !auth_user_can('myplugin.view')) {
        http_response_code(403);
        die('Доступ заборонено');
    }
    
    // Показываем контент
}
```

### Пример 2: Условное отображение элементов

```php
<?php if (auth_user_can('myplugin.settings')): ?>
    <a href="/cabinet/settings">Налаштування</a>
<?php endif; ?>
```

### Пример 3: Регистрация разрешений плагина

```php
public function install(): bool {
    $roleManager = roleManager();
    
    $permissions = [
        ['name' => 'Доступ к плагину', 'slug' => 'myplugin.access', 'category' => 'plugin'],
        ['name' => 'Управление настройками', 'slug' => 'myplugin.settings', 'category' => 'plugin'],
    ];
    
    foreach ($permissions as $perm) {
        $roleManager->createPermission(
            $perm['name'],
            $perm['slug'],
            null,
            $perm['category']
        );
    }
    
    return true;
}
```

## Кеширование

RoleManager использует внутреннее кеширование для оптимизации:
- Кеш ролей пользователя
- Кеш разрешений пользователя
- Кеш разрешений роли

Кеш автоматически очищается при изменении ролей или разрешений. Для ручной очистки используйте методы `clearUserCache()` и `clearRoleCache()`.

## Безопасность

- Системные роли (is_system = 1) нельзя удалить
- Все проверки прав выполняются на сервере
- Кеш обновляется при изменении прав
- Используются подготовленные запросы для защиты от SQL-инъекций

## Расширение

Для добавления новых разрешений используйте хук `register_permissions`:

```php
addFilter('register_permissions', function($permissions) {
    $permissions[] = [
        'name' => 'Мое разрешение',
        'slug' => 'myplugin.custom',
        'description' => 'Описание',
        'category' => 'plugin'
    ];
    return $permissions;
});
```

## Миграция существующих пользователей

При первой установке системы ролей все существующие пользователи должны получить роль "user" или "admin" в зависимости от их статуса.

```php
// Пример миграции
$roleManager = roleManager();
$userRole = $roleManager->getAllRoles();
$userRoleId = null;
foreach ($userRole as $role) {
    if ($role['slug'] === 'user') {
        $userRoleId = $role['id'];
        break;
    }
}

if ($userRoleId) {
    // Назначаем роль всем пользователям
    $stmt = $db->query("SELECT id FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($users as $userId) {
        $roleManager->assignRole($userId, $userRoleId);
    }
}
```

