# Создание плагинов для Flowaxy CMS

## Введение

Плагины - это расширения функциональности Flowaxy CMS. Они позволяют добавлять новый функционал без модификации ядра системы.

## Структура плагина

Каждый плагин должен иметь следующую структуру:

```
plugin-name/
├── plugin.json          # Метаданные плагина
├── index.php            # Точка входа плагина
├── migrations/          # Миграции БД (опционально)
│   └── 001_create_table.php
├── assets/              # Статические файлы (опционально)
│   ├── css/
│   ├── js/
│   └── images/
└── README.md            # Документация плагина (опционально)
```

## plugin.json

Файл метаданных плагина:

```json
{
  "name": "Название плагина",
  "slug": "plugin-slug",
  "version": "1.0.0",
  "description": "Подробное описание функциональности плагина",
  "author": "Имя автора",
  "author_url": "https://example.com",
  "requires": "7.0.0",
  "tested": "8.4",
  "hooks": [
    "hook_name_1",
    "hook_name_2"
  ],
  "dependencies": []
}
```

### Поля метаданных

- `name` - Название плагина (обязательно)
- `slug` - Уникальный идентификатор (обязательно)
- `version` - Версия плагина (обязательно)
- `description` - Описание (обязательно)
- `author` - Автор (обязательно)
- `author_url` - URL автора (опционально)
- `requires` - Минимальная версия Flowaxy CMS (обязательно)
- `tested` - Протестировано на версии PHP (опционально)
- `hooks` - Список используемых хуков (опционально)
- `dependencies` - Зависимости от других плагинов (опционально)

## index.php

Основной файл плагина:

```php
<?php
/**
 * Plugin Name: Мой плагин
 * Description: Описание плагина
 * Version: 1.0.0
 * Author: Автор
 */

declare(strict_types=1);

class MyPlugin extends BasePlugin {
    
    public function init(): void {
        // Регистрация хуков
        addHook('dashboard_widgets', [$this, 'addWidget']);
        addHook('admin_menu', [$this, 'addMenu']);
    }
    
    public function addWidget() {
        return [
            'title' => 'Мой виджет',
            'content' => $this->renderWidget(),
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
    
    private function renderWidget(): string {
        return '<div>Содержимое виджета</div>';
    }
}
```

## Работа с базой данных

### Использование миграций

Создайте файл миграции в `migrations/`:

```php
<?php
// migrations/001_create_table.php

declare(strict_types=1);

return function(PDO $db): bool {
    $sql = "CREATE TABLE IF NOT EXISTS my_plugin_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $db->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Migration error: " . $e->getMessage());
        return false;
    }
};
```

### Прямая работа с БД

```php
public function saveData($data) {
    $db = $this->db;
    $stmt = $db->prepare("INSERT INTO my_plugin_data (name, value) VALUES (?, ?)");
    return $stmt->execute([$data['name'], $data['value']]);
}
```

## Регистрация хуков

### Фильтры

```php
addHook('the_content', function($content) {
    // Модификация контента
    return $content . '<div>Дополнительный контент</div>';
}, 10);
```

### События

```php
addHook('after_save_post', function($post) {
    // Выполнение действия
    sendNotification('Новый пост: ' . $post->title);
}, 10);
```

## Работа с настройками

### Сохранение настроек

```php
$settings = settingsManager();
$settings->set('my_plugin_option', 'value');
```

### Получение настроек

```php
$settings = settingsManager();
$value = $settings->get('my_plugin_option', 'default');
```

## Добавление страниц в админку

### Регистрация маршрута

```php
public function init(): void {
    addHook('admin_routes', [$this, 'registerRoutes']);
}

public function registerRoutes() {
    return [
        '/admin/my-plugin' => [$this, 'handlePage']
    ];
}

public function handlePage() {
    // Обработка страницы
    include __DIR__ . '/templates/admin-page.php';
}
```

## Работа с файлами

### Загрузка файлов

```php
use Engine\Classes\Files\Upload;

$upload = new Upload();
$file = $upload->handle('file_input', [
    'allowed_types' => ['image/jpeg', 'image/png'],
    'max_size' => 5 * 1024 * 1024, // 5MB
    'upload_dir' => UPLOADS_DIR . 'my-plugin/'
]);
```

## Логирование

```php
$logger = logger();
$logger->logInfo('Плагин активирован');
$logger->logError('Ошибка выполнения', ['context' => $data]);
```

## Кеширование

```php
$cache = cache();

// Сохранение в кеш
$cache->set('my_plugin_data', $data, 3600);

// Получение из кеша
$data = $cache->get('my_plugin_data', null);
```

## Лучшие практики

1. **Используйте пространства имен** - избегайте конфликтов имен
2. **Валидируйте данные** - всегда проверяйте входные данные
3. **Используйте prepared statements** - для всех SQL запросов
4. **Логируйте ошибки** - для отладки
5. **Документируйте код** - для поддержки
6. **Тестируйте плагин** - перед публикацией
7. **Следуйте стандартам** - PSR-12 для кода

## Установка плагина

1. Создайте ZIP архив плагина
2. Загрузите через админ-панель: `/admin/plugins`
3. Активируйте плагин

## Удаление плагина

При удалении плагина:
1. Выполняются миграции отката (если есть)
2. Удаляются данные плагина (если настроено)
3. Удаляются файлы плагина

## Примеры

См. [Примеры плагинов](../examples/plugins.md) для полных примеров кода.

