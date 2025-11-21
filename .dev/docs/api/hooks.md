# Система хуков и событий

Flowaxy CMS использует мощную систему хуков для расширения функциональности без модификации ядра.

## Введение

Хуки позволяют плагинам и темам интегрироваться с системой в определенные моменты выполнения кода. Система поддерживает два типа хуков:

- **Фильтры (Filters)** - модифицируют данные
- **События (Actions)** - выполняют действия

## Типы хуков

### Фильтры (Filters)

Фильтры позволяют модифицировать данные перед их использованием. Они принимают значение, модифицируют его и возвращают новое значение.

```php
$content = applyFilter('content', $originalContent);
```

### События (Actions)

События выполняют действия в определенные моменты. Они не возвращают значения, а просто выполняют код.

```php
doAction('before_save_post', $post);
```

## Регистрация хуков

### addHook()

Регистрация хука:

```php
addHook(string $hookName, callable $callback, int $priority = 10): void
```

**Параметры:**
- `$hookName` - имя хука
- `$callback` - функция обратного вызова
- `$priority` - приоритет (меньше = выше приоритет, по умолчанию 10)

**Пример:**
```php
addHook('dashboard_widgets', function() {
    return [
        'title' => 'Мой виджет',
        'content' => 'Содержимое',
        'order' => 10
    ];
}, 5);
```

### Условная регистрация

Хуки можно регистрировать условно:

```php
addHook('admin_menu', function() {
    if (currentUser()->hasRole('developer')) {
        return [
            'title' => 'Разработка',
            'url' => '/admin/dev',
            'icon' => 'fas fa-code'
        ];
    }
    return null;
});
```

## Использование хуков

### applyFilter()

Применение фильтра:

```php
$value = applyFilter(string $hookName, $value, ...$args): mixed
```

**Пример:**
```php
$content = applyFilter('post_content', $post->content, $post);
```

### doAction()

Выполнение события:

```php
doAction(string $hookName, ...$args): void
```

**Пример:**
```php
doAction('before_save_user', $user);
```

## Доступные хуки

### Хуки админ-панели

- `admin_menu` - добавление пунктов меню
- `dashboard_widgets` - виджеты на главной панели
- `admin_footer` - код в футере админки
- `admin_header` - код в хедере админки

### Хуки контента

- `the_content` - фильтр контента
- `before_save_post` - перед сохранением поста
- `after_save_post` - после сохранения поста
- `before_delete_post` - перед удалением поста

### Хуки пользователей

- `before_save_user` - перед сохранением пользователя
- `after_save_user` - после сохранения пользователя
- `user_login` - при входе пользователя
- `user_logout` - при выходе пользователя

### Хуки плагинов

- `plugin_activated` - при активации плагина
- `plugin_deactivated` - при деактивации плагина
- `plugin_installed` - при установке плагина
- `plugin_uninstalled` - при удалении плагина

### Хуки тем

- `theme_activated` - при активации темы
- `theme_customizer` - настройки кастомизатора

## Примеры использования

### Пример 1: Добавление виджета на главную панель

```php
<?php
class MyPlugin extends BasePlugin {
    public function init() {
        addHook('dashboard_widgets', [$this, 'addWidget']);
    }
    
    public function addWidget() {
        return [
            'title' => 'Статистика',
            'content' => $this->getStats(),
            'order' => 10
        ];
    }
    
    private function getStats(): string {
        // Получение статистики
        return '<div>Статистика...</div>';
    }
}
```

### Пример 2: Модификация контента

```php
<?php
addHook('the_content', function($content) {
    // Добавляем кнопку "Поделиться" в конец контента
    return $content . '<div class="share-buttons">...</div>';
});
```

### Пример 3: Выполнение действия при сохранении

```php
<?php
addHook('after_save_post', function($post) {
    // Отправка уведомления
    sendNotification('Новый пост создан: ' . $post->title);
});
```

## Приоритеты хуков

Хуки выполняются в порядке приоритета (меньше = раньше):

- `1-5` - Критически важные хуки
- `6-10` - Стандартные хуки (по умолчанию)
- `11-20` - Дополнительные хуки
- `21+` - Низкоприоритетные хуки

## Удаление хуков

### removeHook()

Удаление зарегистрированного хука:

```php
removeHook(string $hookName, callable $callback): bool
```

**Пример:**
```php
$callback = function() { /* ... */ };
addHook('hook_name', $callback);
// ...
removeHook('hook_name', $callback);
```

## Проверка наличия хука

### hasHook()

Проверка, зарегистрирован ли хук:

```php
hasHook(string $hookName): bool
```

## Лучшие практики

1. **Используйте осмысленные имена хуков** - `plugin_name_action`
2. **Проверяйте контекст** - не все хуки доступны везде
3. **Используйте правильные приоритеты** - для критичных хуков используйте низкие приоритеты
4. **Не злоупотребляйте хуками** - не регистрируйте хуки, которые не нужны
5. **Документируйте свои хуки** - если создаете плагин, документируйте хуки, которые он использует

## Отладка хуков

Для отладки можно использовать:

```php
// Проверка зарегистрированных хуков
$hooks = HookManager::getInstance()->getHooks('hook_name');
var_dump($hooks);
```

---

**Полная документация по хукам:** [HOOKS_DOCUMENTATION.md](../../wiki/engine/classes/system/HOOKS_DOCUMENTATION.md)

