# Создание тем для Flowaxy CMS

## Введение

Темы определяют визуальное оформление сайта. Каждая тема может иметь собственные шаблоны, стили и функциональность.

## Структура темы

Каждая тема должна иметь следующую структуру:

```
theme-name/
├── theme.json           # Метаданные темы
├── index.php            # Основной файл темы
├── style.css            # Основные стили темы
├── functions.php        # Функции темы (опционально)
├── screenshot.png       # Превью темы (1200x900px)
├── templates/           # Шаблоны (опционально)
│   ├── header.php
│   ├── footer.php
│   └── single.php
├── assets/              # Статические файлы (опционально)
│   ├── css/
│   ├── js/
│   └── images/
└── README.md            # Документация темы (опционально)
```

## theme.json

Файл метаданных темы:

```json
{
  "name": "Название темы",
  "slug": "theme-slug",
  "version": "1.0.0",
  "description": "Описание темы оформления",
  "author": "Имя автора",
  "author_url": "https://example.com",
  "requires": "7.0.0",
  "screenshot": "screenshot.png"
}
```

### Поля метаданных

- `name` - Название темы (обязательно)
- `slug` - Уникальный идентификатор (обязательно)
- `version` - Версия темы (обязательно)
- `description` - Описание (обязательно)
- `author` - Автор (обязательно)
- `author_url` - URL автора (опционально)
- `requires` - Минимальная версия Flowaxy CMS (обязательно)
- `screenshot` - Имя файла скриншота (опционально)

## index.php

Основной файл темы:

```php
<?php
/**
 * Theme Name: Моя тема
 * Description: Описание темы
 * Version: 1.0.0
 * Author: Автор
 */

declare(strict_types=1);

// Регистрация маршрутов
Router::get('/', function() {
    return View::make('home', [
        'title' => 'Главная страница'
    ]);
});

Router::get('/about', function() {
    return View::make('about', [
        'title' => 'О нас'
    ]);
});

// Использование хуков
addHook('before_content', function() {
    echo '<div class="container">';
});

addHook('after_content', function() {
    echo '</div>';
});
```

## style.css

Основные стили темы:

```css
/*
Theme Name: Моя тема
Description: Описание темы
Version: 1.0.0
Author: Автор
*/

body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
```

## functions.php

Функции темы:

```php
<?php
declare(strict_types=1);

// Регистрация меню
addHook('theme_menus', function() {
    return [
        'primary' => 'Основное меню',
        'footer' => 'Меню в футере'
    ];
});

// Регистрация областей виджетов
addHook('theme_sidebars', function() {
    return [
        'sidebar-1' => 'Боковая панель',
        'footer-1' => 'Футер колонка 1',
        'footer-2' => 'Футер колонка 2'
    ];
});

// Подключение скриптов и стилей
addHook('wp_enqueue_scripts', function() {
    // Подключение стилей
    echo '<link rel="stylesheet" href="' . getThemeUrl() . '/assets/css/style.css">';
    
    // Подключение скриптов
    echo '<script src="' . getThemeUrl() . '/assets/js/script.js"></script>';
});
```

## Работа с шаблонами

### Создание шаблона

```php
<?php
// templates/home.php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title ?? 'Главная') ?></title>
</head>
<body>
    <?php doAction('before_content'); ?>
    
    <main>
        <h1>Добро пожаловать!</h1>
        <?= $content ?? '' ?>
    </main>
    
    <?php doAction('after_content'); ?>
</body>
</html>
```

### Использование View

```php
Router::get('/post/{id}', function($id) {
    $post = getPost($id);
    
    return View::make('single', [
        'post' => $post,
        'title' => $post->title
    ]);
});
```

## Кастомизация темы

### Настройки темы

```php
addHook('theme_customizer', function() {
    return [
        'colors' => [
            'primary' => [
                'label' => 'Основной цвет',
                'default' => '#0073aa',
                'type' => 'color'
            ],
            'secondary' => [
                'label' => 'Вторичный цвет',
                'default' => '#005a87',
                'type' => 'color'
            ]
        ],
        'layout' => [
            'sidebar' => [
                'label' => 'Показывать боковую панель',
                'default' => true,
                'type' => 'checkbox'
            ]
        ]
    ];
});
```

### Получение настроек

```php
$customizer = themeCustomizer();
$primaryColor = $customizer->get('colors.primary', '#0073aa');
$showSidebar = $customizer->get('layout.sidebar', true);
```

## Работа с хуками

### Хуки контента

```php
// Модификация контента
addHook('the_content', function($content) {
    // Добавить кнопку "Поделиться"
    return $content . '<div class="share-buttons">...</div>';
});

// Добавление метаданных
addHook('post_meta', function($post) {
    echo '<div class="post-meta">';
    echo '<span>Автор: ' . htmlspecialchars($post->author) . '</span>';
    echo '<span>Дата: ' . date('d.m.Y', strtotime($post->created_at)) . '</span>';
    echo '</div>';
});
```

## Подключение ресурсов

### CSS и JavaScript

```php
addHook('wp_head', function() {
    $themeUrl = getThemeUrl();
    echo '<link rel="stylesheet" href="' . $themeUrl . '/assets/css/main.css">';
});

addHook('wp_footer', function() {
    $themeUrl = getThemeUrl();
    echo '<script src="' . $themeUrl . '/assets/js/main.js"></script>';
});
```

## Работа с меню

```php
// Регистрация меню
addHook('theme_menus', function() {
    return [
        'primary' => 'Основное меню',
        'footer' => 'Меню в футере'
    ];
});

// Вывод меню
function displayMenu(string $location): void {
    $menu = getMenu($location);
    if ($menu) {
        echo '<nav>';
        foreach ($menu->items as $item) {
            echo '<a href="' . htmlspecialchars($item->url) . '">';
            echo htmlspecialchars($item->title);
            echo '</a>';
        }
        echo '</nav>';
    }
}
```

## Лучшие практики

1. **Используйте функции-хелперы** - для получения данных
2. **Экранируйте вывод** - всегда используйте `htmlspecialchars()`
3. **Оптимизируйте ресурсы** - минифицируйте CSS/JS
4. **Используйте кеширование** - для тяжелых операций
5. **Документируйте код** - для поддержки
6. **Тестируйте тему** - на разных устройствах
7. **Следуйте стандартам** - PSR-12 для кода

## Установка темы

1. Создайте ZIP архив темы
2. Загрузите через админ-панель: `/admin/themes`
3. Активируйте тему

## Примеры

См. [Примеры тем](../examples/themes.md) для полных примеров кода.

