# Структура шаблонов админки Flowaxy CMS

## Структура папок

```
engine/skins/
├── assets/              # Статические ресурсы
│   ├── images/         # Изображения (логотипы, иконки)
│   ├── scripts/        # JavaScript файлы
│   └── styles/         # CSS файлы
├── components/         # Переиспользуемые компоненты
│   ├── button.php      # Компонент кнопки
│   ├── card.php        # Компонент карточки
│   ├── form-group.php  # Компонент группы формы
│   ├── header.php      # Шапка сайта
│   ├── sidebar.php     # Боковая панель
│   ├── footer.php      # Подвал
│   ├── page-header.php # Заголовок страницы
│   ├── notifications.php # Система уведомлений
│   └── scripts.php     # JavaScript скрипты
├── includes/           # Вспомогательные классы
│   ├── AdminPage.php   # Базовый класс страниц админки
│   ├── ComponentHelper.php # Хелпер для работы с компонентами
│   ├── SimpleTemplate.php  # Простой шаблонизатор
│   ├── menu-items.php  # Меню админки
│   └── admin-routes.php # Маршруты админки
├── pages/              # Страницы админки (PHP классы)
│   ├── DashboardPage.php
│   ├── SettingsPage.php
│   ├── PluginsPage.php
│   ├── ThemesPage.php
│   └── ...
└── templates/          # Шаблоны страниц
    ├── layout/         # Базовые layouts
    │   ├── base.php    # Основной layout
    │   └── base-plugin.php # Layout для плагинов
    ├── dashboard.php   # Шаблон dashboard
    ├── settings.php    # Шаблон настроек
    ├── plugins.php     # Шаблон плагинов
    ├── themes.php      # Шаблон тем
    ├── list-page.php   # Универсальный шаблон для списков
    └── ...
```

## Использование компонентов

### Включение компонента в шаблон

```php
<?php
// Подключаем хелпер
require_once __DIR__ . '/../../includes/ComponentHelper.php';

// Включаем компонент кнопки
includeComponent('button', [
    'text' => 'Сохранить',
    'type' => 'primary',
    'icon' => 'save',
    'submit' => true,
    'attributes' => [
        'class' => 'btn-lg',
        'data-action' => 'save'
    ]
]);
?>
```

### Использование в шаблонах

Компоненты можно использовать двумя способами:

1. **Прямое включение:**
```php
<?php include __DIR__ . '/../../components/button.php'; ?>
```

2. **Через хелпер (рекомендуется):**
```php
<?php includeComponent('button', ['text' => 'Сохранить', 'type' => 'primary']); ?>
```

## Доступные компоненты

### button.php
Компонент кнопки. Параметры:
- `text` (string) - Текст кнопки
- `type` (string) - Тип кнопки: `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `outline-primary`, и т.д.
- `url` (string) - URL для ссылки (если указан, создается `<a>`, иначе `<button>`)
- `icon` (string) - Иконка Font Awesome (без префикса `fa-`)
- `attributes` (array) - Дополнительные атрибуты HTML
- `submit` (bool) - Если `true`, кнопка будет `type="submit"`

Пример:
```php
includeComponent('button', [
    'text' => 'Удалить',
    'type' => 'danger',
    'icon' => 'trash',
    'attributes' => [
        'onclick' => 'confirmDelete()',
        'data-id' => '123'
    ]
]);
```

### card.php
Компонент карточки. Параметры:
- `title` (string) - Заголовок карточки
- `content` (string) - Содержимое карточки (HTML)
- `header` (string) - Дополнительный контент в заголовке (кнопки)
- `footer` (string) - Контент в футере
- `classes` (array) - Дополнительные CSS классы

Пример:
```php
includeComponent('card', [
    'title' => 'Настройки',
    'content' => '<p>Содержимое карточки</p>',
    'header' => '<button class="btn btn-sm">Редактировать</button>',
    'classes' => ['shadow-sm', 'border-0']
]);
```

### form-group.php
Компонент группы формы. Параметры:
- `label` (string) - Метка поля
- `name` (string) - Имя поля
- `type` (string) - Тип поля: `text`, `email`, `password`, `textarea`, `select`, `checkbox`, и т.д.
- `value` (mixed) - Значение поля
- `placeholder` (string) - Placeholder
- `helpText` (string) - Подсказка под полем
- `options` (array) - Опции для select/radio/checkbox (массив `value => label`)
- `attributes` (array) - Дополнительные атрибуты
- `id` (string) - ID поля (если не указан, генерируется из name)

Пример:
```php
includeComponent('form-group', [
    'label' => 'Email',
    'name' => 'email',
    'type' => 'email',
    'value' => $userEmail,
    'placeholder' => 'email@example.com',
    'helpText' => 'Введите ваш email адрес',
    'attributes' => [
        'required' => true,
        'class' => 'form-control-lg'
    ]
]);
```

## Использование шаблонов

### Базовый layout

Все страницы админки используют базовый layout из `layouts/base.php`, который включает:
- Header (шапка)
- Sidebar (боковая панель)
- Основной контент
- Footer (подвал)
- Уведомления
- JavaScript скрипты

### Универсальный шаблон для списков

Шаблон `list-page.php` предназначен для страниц со списком элементов (plugins, themes, и т.д.).

Параметры:
- `items` (array) - Массив элементов для отображения
- `itemTemplate` (string) - Путь к шаблону одного элемента (без расширения .php)
- `emptyMessage` (string) - Сообщение, если список пуст
- `emptyIcon` (string) - Иконка для пустого состояния
- `actions` (string) - Дополнительные действия (кнопки) в заголовке списка

Пример использования в странице:
```php
// В методе handle() страницы
$items = [
    ['name' => 'Plugin 1', 'version' => '1.0.0'],
    ['name' => 'Plugin 2', 'version' => '2.0.0'],
];

$this->render([
    'items' => $items,
    'itemTemplate' => 'plugin-item', // templates/plugin-item.php
    'emptyMessage' => 'Плагины не найдены',
    'emptyIcon' => 'puzzle-piece',
    'actions' => '<button class="btn btn-primary">Установить плагин</button>'
]);
```

## Хелперы

### ComponentHelper

Доступные функции:
- `includeComponent($name, $data)` - Включить компонент
- `getComponent($name, $data)` - Получить содержимое компонента в строку
- `includeTemplate($name, $data)` - Включить шаблон
- `getTemplate($name, $data)` - Получить содержимое шаблона в строку
- `asset($path)` - Получить URL к ассету

Пример:
```php
// Получить URL к изображению
$logoUrl = asset('images/brand/logo-white.png');
// Результат: /admin/assets/images/brand/logo-white.png

// Получить HTML кнопки в строку
$buttonHtml = getComponent('button', ['text' => 'Сохранить', 'type' => 'primary']);
```

## Пути к ассетам

Все пути к ассетам должны начинаться с `/admin/assets/`:
- CSS: `/admin/assets/styles/flowaxy.css`
- JS: `/admin/assets/scripts/ajax-helper.js`
- Изображения: `/admin/assets/images/brand/logo-white.png`

Или используйте хелпер `asset()`:
```php
$cssUrl = asset('styles/flowaxy.css');
```

## Создание новых компонентов

1. Создайте файл в `engine/skins/components/your-component.php`
2. Используйте переменные из массива `$data`, переданного в `includeComponent()`
3. Проверяйте существование переменных через `isset()`

Пример:
```php
<?php
// engine/skins/components/your-component.php
if (!isset($text)) {
    $text = 'По умолчанию';
}
if (!isset($classes)) {
    $classes = [];
}

$classString = implode(' ', array_map('htmlspecialchars', $classes));
?>
<div class="<?= $classString ?>">
    <?= htmlspecialchars($text) ?>
</div>
```

## Создание новых шаблонов

1. Создайте файл в `engine/skins/templates/your-template.php`
2. Используйте переменные из массива `$data`, переданного в `render()`
3. Включайте компоненты через `includeComponent()` или `include __DIR__ . '/../components/component-name.php'`

Пример:
```php
<?php
// engine/skins/templates/your-template.php
?>
<div class="your-page">
    <?php includeComponent('page-header', [
        'title' => $pageTitle ?? 'Заголовок',
        'description' => $pageDescription ?? ''
    ]); ?>
    
    <div class="content">
        <?= $content ?? '' ?>
    </div>
</div>
```

## Миграция существующих шаблонов

При обновлении существующих шаблонов:
1. Замените прямые пути к статическим файлам на использование `asset()`
2. Замените `include` компонентов на `includeComponent()`
3. Используйте компоненты вместо дублирования кода

