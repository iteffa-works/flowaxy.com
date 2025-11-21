# Базовые классы

Базовые классы предоставляют основу для создания модулей и плагинов в Flowaxy CMS.

## BaseModule

Базовый класс для всех системных модулей.

### Описание
`BaseModule` предоставляет общую функциональность для всех модулей системы, включая доступ к базе данных и базовую инициализацию.

### Расположение
`engine/classes/base/BaseModule.php`

### Основные свойства

```php
protected ?PDO $db = null;
```

### Основные методы

#### `__construct()`
Конструктор модуля. Инициализирует подключение к базе данных.

### Пример использования

```php
<?php
class MyModule extends BaseModule {
    public function __construct() {
        parent::__construct();
        // Ваша инициализация
    }
    
    public function doSomething() {
        // Использование $this->db для работы с БД
        $stmt = $this->db->prepare("SELECT * FROM table");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
```

---

## BasePlugin

Базовый класс для всех плагинов системы.

### Описание
`BasePlugin` предоставляет базовую функциональность для плагинов, включая доступ к конфигурации, базе данных и метаданным плагина.

### Расположение
`engine/classes/base/BasePlugin.php`

### Основные свойства

```php
protected ?array $pluginData = null;  // Метаданные плагина
protected ?PDO $db = null;            // Подключение к БД
protected array $config = [];         // Конфигурация плагина
```

### Основные методы

#### `__construct(string $pluginPath)`
Конструктор плагина. Загружает метаданные из `plugin.json`.

**Параметры:**
- `$pluginPath` - путь к директории плагина

#### `init()`
Метод инициализации плагина. Переопределяется в дочерних классах.

#### `getPluginData(): ?array`
Возвращает метаданные плагина.

#### `getConfig(string $key, $default = null)`
Получает значение конфигурации плагина.

**Параметры:**
- `$key` - ключ конфигурации
- `$default` - значение по умолчанию

#### `setConfig(string $key, $value): void`
Устанавливает значение конфигурации плагина.

### Структура plugin.json

```json
{
  "name": "Название плагина",
  "slug": "plugin-slug",
  "version": "1.0.0",
  "description": "Описание плагина",
  "author": "Автор",
  "author_url": "https://example.com",
  "requires": "7.0.0",
  "tested": "8.4",
  "hooks": ["hook_name_1", "hook_name_2"],
  "dependencies": []
}
```

### Пример использования

```php
<?php
class MyPlugin extends BasePlugin {
    public function init() {
        // Регистрация хуков
        addHook('dashboard_widgets', [$this, 'addWidget']);
        addHook('admin_menu', [$this, 'addMenu']);
    }
    
    public function addWidget() {
        return [
            'title' => 'Мой виджет',
            'content' => 'Содержимое виджета',
            'order' => 10
        ];
    }
    
    public function addMenu() {
        return [
            'title' => 'Мой плагин',
            'url' => '/admin/my-plugin',
            'icon' => 'fas fa-cog',
            'order' => 20
        ];
    }
}
```

### Жизненный цикл плагина

1. **Загрузка** - Плагин загружается через `PluginManager`
2. **Инициализация** - Вызывается метод `init()`
3. **Регистрация хуков** - Плагин регистрирует свои хуки
4. **Активация** - Плагин становится активным
5. **Выполнение** - Хуки плагина выполняются при соответствующих событиях
6. **Деактивация** - Плагин деактивируется
7. **Удаление** - Плагин удаляется из системы

### Рекомендации

- Всегда переопределяйте метод `init()` для регистрации хуков
- Используйте `$this->db` для работы с базой данных
- Храните конфигурацию через методы `getConfig()` и `setConfig()`
- Используйте хуки для интеграции с системой
- Следуйте структуре `plugin.json` для метаданных

