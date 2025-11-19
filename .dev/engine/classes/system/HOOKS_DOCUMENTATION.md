# Документация по системе хуков и событий Flowaxy CMS

## Оглавление

1. [Введение](#введение)
2. [Типы хуков](#типы-хуков)
3. [Базовое использование](#базовое-использование)
4. [Фильтры (Filters)](#фильтры-filters)
5. [События (Actions)](#события-actions)
6. [Условное выполнение](#условное-выполнение)
7. [Приоритеты](#приоритеты)
8. [Удаление хуков](#удаление-хуков)
9. [Примеры использования](#примеры-использования)
10. [Стандартные хуки системы](#стандартные-хуки-системы)

---

## Введение

Система хуков Flowaxy CMS позволяет расширять функциональность системы без изменения ядра. Хуки делятся на два типа:

- **Фильтры (Filters)** - модифицируют данные и возвращают результат
- **События (Actions)** - выполняют действия без возврата данных

---

## Типы хуков

### Фильтры (Filters)

Фильтры используются для модификации данных. Каждый фильтр получает данные, может их изменить и должен вернуть результат.

**Пример:**
```php
// Добавление фильтра
addFilter('post_title', function($title) {
    return strtoupper($title);
});

// Применение фильтра
$title = applyFilter('post_title', 'Hello World'); // Вернет "HELLO WORLD"
```

### События (Actions)

События используются для выполнения действий в определенные моменты. Они не возвращают данные.

**Пример:**
```php
// Добавление события
addAction('user_registered', function($userId) {
    // Отправить email приветствия
    sendWelcomeEmail($userId);
});

// Выполнение события
doAction('user_registered', 123);
```

---

## Базовое использование

### Обратная совместимость

Для обратной совместимости сохранены старые функции:

```php
// Старый способ (все еще работает)
addHook('hook_name', $callback, 10);
$result = doHook('hook_name', $data);
$exists = hasHook('hook_name');
```

По умолчанию `addHook()` добавляет фильтр.

---

## Фильтры (Filters)

### Добавление фильтра

```php
/**
 * @param string $hookName Имя хука
 * @param callable $callback Функция обратного вызова
 * @param int $priority Приоритет (по умолчанию 10)
 * @param callable|null $condition Условие выполнения (опционально)
 */
addFilter('hook_name', $callback, $priority = 10, $condition = null);
```

### Применение фильтра

```php
/**
 * @param string $hookName Имя хука
 * @param mixed $data Данные для фильтрации
 * @param mixed ...$args Дополнительные аргументы
 * @return mixed Отфильтрованные данные
 */
$result = applyFilter('hook_name', $data, ...$args);
```

### Примеры фильтров

#### Модификация заголовка поста

```php
// В плагине
addFilter('post_title', function($title) {
    return '⭐ ' . $title;
}, 10);

// В коде темы
$title = applyFilter('post_title', 'Мой пост');
// Результат: "⭐ Мой пост"
```

#### Фильтрация списка меню

```php
// Добавление пункта меню
addFilter('admin_menu', function($menu) {
    $menu[] = [
        'title' => 'Мой раздел',
        'url' => '/admin/my-section',
        'icon' => 'fa-star',
        'order' => 20
    ];
    return $menu;
});

// Применение
$menu = applyFilter('admin_menu', []);
```

#### Множественные фильтры

```php
// Фильтр 1: Добавить префикс
addFilter('content', function($content) {
    return '<div class="wrapper">' . $content;
}, 5);

// Фильтр 2: Добавить суффикс
addFilter('content', function($content) {
    return $content . '</div>';
}, 15);

// Результат: <div class="wrapper">[содержимое]</div>
```

---

## События (Actions)

### Добавление события

```php
/**
 * @param string $hookName Имя хука
 * @param callable $callback Функция обратного вызова
 * @param int $priority Приоритет (по умолчанию 10)
 * @param callable|null $condition Условие выполнения (опционально)
 */
addAction('hook_name', $callback, $priority = 10, $condition = null);
```

### Выполнение события

```php
/**
 * @param string $hookName Имя хука
 * @param mixed ...$args Аргументы для передачи в обработчики
 */
doAction('hook_name', ...$args);
```

### Примеры событий

#### Отправка email при регистрации

```php
// В плагине
addAction('user_registered', function($userId, $userData) {
    $email = $userData['email'];
    sendEmail($email, 'Добро пожаловать!', 'Спасибо за регистрацию');
});

// В коде регистрации
doAction('user_registered', $userId, $userData);
```

#### Логирование действий

```php
// Добавление логирования
addAction('post_published', function($postId) {
    logger()->logInfo("Post published: {$postId}");
});

// Выполнение
doAction('post_published', 123);
```

#### Множественные обработчики

```php
// Обработчик 1: Отправить email
addAction('order_created', function($orderId) {
    sendOrderConfirmation($orderId);
}, 10);

// Обработчик 2: Обновить статистику
addAction('order_created', function($orderId) {
    updateOrderStatistics($orderId);
}, 20);

// Выполнение
doAction('order_created', 456);
// Оба обработчика будут вызваны
```

---

## Условное выполнение

Хуки могут выполняться только при определенных условиях:

```php
// Фильтр с условием
addFilter('post_content', function($content) {
    return $content . '<p>Реклама</p>';
}, 10, function($content) {
    // Выполнять только для постов длиннее 1000 символов
    return strlen($content) > 1000;
});

// Событие с условием
addAction('page_view', function($pageId) {
    trackPageView($pageId);
}, 10, function($pageId) {
    // Выполнять только для определенных страниц
    return in_array($pageId, [1, 2, 3]);
});
```

---

## Приоритеты

Приоритет определяет порядок выполнения хуков. Меньшее значение = раньше выполняется.

**Стандартные приоритеты:**
- `1-5` - Очень высокий приоритет (выполняется первым)
- `6-9` - Высокий приоритет
- `10` - Стандартный приоритет (по умолчанию)
- `11-19` - Низкий приоритет
- `20+` - Очень низкий приоритет (выполняется последним)

**Пример:**

```php
// Выполнится первым (приоритет 5)
addFilter('content', function($content) {
    return '<div class="start">' . $content;
}, 5);

// Выполнится вторым (приоритет 10)
addFilter('content', function($content) {
    return $content . '<p>Средний</p>';
}, 10);

// Выполнится последним (приоритет 20)
addFilter('content', function($content) {
    return $content . '</div>';
}, 20);
```

---

## Удаление хуков

### Удаление конкретного хука

```php
// Удалить конкретный callback
$callback = function($data) { return $data; };
addFilter('hook_name', $callback);
// ...
removeHook('hook_name', $callback);
```

### Удаление всех хуков

```php
// Удалить все хуки с этим именем
removeHook('hook_name');
```

### Удаление хуков плагина

При деактивации плагина его хуки автоматически удаляются системой.

---

## Примеры использования

### Пример 1: Плагин для добавления виджета в админку

```php
class MyWidgetPlugin extends BasePlugin {
    public function init() {
        // Добавляем виджет через фильтр
        addFilter('dashboard_widgets', [$this, 'addWidget'], 10);
    }
    
    public function addWidget($widgets) {
        $widgets[] = [
            'title' => 'Мой виджет',
            'content' => '<p>Содержимое виджета</p>',
            'order' => 10
        ];
        return $widgets;
    }
}
```

### Пример 2: Плагин для логирования действий

```php
class LoggerPlugin extends BasePlugin {
    public function init() {
        // Логируем различные события
        addAction('user_login', [$this, 'logUserLogin'], 10);
        addAction('post_created', [$this, 'logPostCreated'], 10);
        addAction('plugin_activated', [$this, 'logPluginActivated'], 10);
    }
    
    public function logUserLogin($userId) {
        logger()->logInfo("User logged in: {$userId}");
    }
    
    public function logPostCreated($postId) {
        logger()->logInfo("Post created: {$postId}");
    }
    
    public function logPluginActivated($pluginSlug) {
        logger()->logInfo("Plugin activated: {$pluginSlug}");
    }
}
```

### Пример 3: Модификация контента с условием

```php
class ContentModifierPlugin extends BasePlugin {
    public function init() {
        // Добавляем рекламу только для гостей
        addFilter('post_content', [$this, 'addAdvertisement'], 20, function($content) {
            return !isUserLoggedIn();
        });
    }
    
    public function addAdvertisement($content) {
        return $content . '<div class="ad">Реклама</div>';
    }
}
```

### Пример 4: Использование в теме

```php
// В functions.php темы
addAction('theme_head', function() {
    echo '<link rel="stylesheet" href="/custom.css">';
});

addFilter('theme_title', function($title) {
    return $title . ' | Мой сайт';
});

// В шаблоне
$title = applyFilter('theme_title', 'Главная страница');
doAction('theme_head');
```

---

## Стандартные хуки системы

### Административная панель

- `admin_menu` - Фильтр для добавления пунктов меню админки
- `admin_register_routes` - Событие для регистрации маршрутов админки
- `dashboard_widgets` - Фильтр для добавления виджетов на главную панель
- `handle_early_request` - Событие для обработки ранних запросов

### Плагины

- `plugin_installed` - Событие после установки плагина
- `plugin_uninstalled` - Событие после удаления плагина
- `plugin_activated` - Событие после активации плагина
- `plugin_deactivated` - Событие после деактивации плагина

### Темы

- `theme_head` - Событие для вывода в `<head>`
- `theme_footer` - Событие для вывода в `<footer>`
- `theme_content` - Фильтр для модификации контента
- `theme_menu` - Событие для вывода меню
- `theme_widgets` - Фильтр для виджетов темы

### База данных

- `db_error` - Событие при ошибке БД
- `db_query` - Событие при выполнении запроса
- `db_slow_query` - Событие при медленном запросе

### Модули

- `module_loaded` - Событие после загрузки модуля
- `module_error` - Событие при ошибке загрузки модуля

### Роутинг

- `register_routes` - Событие для регистрации маршрутов

---

## Лучшие практики

1. **Используйте правильный тип хука:**
   - Фильтры для модификации данных
   - События для выполнения действий

2. **Указывайте приоритеты:**
   - Используйте стандартные приоритеты (5, 10, 15, 20)
   - Документируйте, почему выбран конкретный приоритет

3. **Используйте условия:**
   - Условное выполнение улучшает производительность
   - Условия делают код более понятным

4. **Удаляйте хуки при деактивации:**
   - Система делает это автоматически, но можно и вручную

5. **Документируйте свои хуки:**
   - Указывайте, какой тип хука используется
   - Описывайте параметры и возвращаемые значения

---

## Отладка

### Проверка существования хука

```php
if (hasHook('hook_name')) {
    // Хук зарегистрирован
}
```

### Получение статистики хуков

```php
$stats = hookManager()->getHookStats();
// Возвращает:
// - total_hooks: общее количество хуков
// - hook_calls: количество вызовов каждого хука
// - hooks_list: список всех хуков
```

### Получение всех хуков

```php
$allHooks = hookManager()->getAllHooks();
// Возвращает массив всех зарегистрированных хуков
```

---

## Заключение

Система хуков Flowaxy CMS предоставляет гибкий и мощный механизм расширения функциональности. Используйте фильтры для модификации данных и события для выполнения действий в нужные моменты.

Для дополнительной информации обращайтесь к исходному коду класса `HookManager`.

