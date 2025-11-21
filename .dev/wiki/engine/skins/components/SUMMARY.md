# Компоненты админки Flowaxy CMS

## Список компонентов

### Базовые компоненты

1. **alert.php** - Уведомления/алерты
   - Параметры: `message`, `type` (success/info/warning/danger), `dismissible`, `icon`, `classes`
   - Использование: Заменяет все `<div class="alert">` в шаблонах

2. **badge.php** - Бейджи/метки
   - Параметры: `text`, `type` (primary/secondary/success/danger/warning/info/active/installed/available/inactive), `icon`, `classes`
   - Использование: Бейджи статусов, версий, категорий

3. **button.php** - Кнопки
   - Параметры: `text`, `type`, `url` (для ссылок), `icon`, `attributes`, `submit`
   - Использование: Все кнопки в админке

4. **card.php** - Карточки
   - Параметры: `title`, `content`, `header`, `footer`, `classes`
   - Использование: Карточки контента

5. **empty-state.php** - Пустые состояния
   - Параметры: `icon`, `title`, `message`, `actions` (HTML кнопок), `classes`
   - Использование: Когда список пуст (нет плагинов, тем, и т.д.)

6. **modal.php** - Модальные окна
   - Параметры: `id` (обязательно), `title`, `content`, `footer`, `size` (sm/lg/xl), `centered`
   - Использование: Модальные окна

7. **upload-modal.php** - Модальные окна загрузки файлов
   - Параметры: `id`, `title`, `fileInputName`, `action`, `accept`, `helpText`, `maxSize`
   - Использование: Загрузка плагинов, тем, файлов

8. **content-section.php** - Секции контента
   - Параметры: `title`, `icon`, `content`, `header` (кнопки в заголовке), `classes`
   - Использование: Обертка для секций страниц (plugins, themes)

9. **form-group.php** - Группы форм
   - Параметры: `label`, `name`, `type` (text/email/password/textarea/select/checkbox), `value`, `placeholder`, `helpText`, `options`, `attributes`, `id`
   - Использование: Поля форм

10. **plugin-card.php** - Карточки плагинов
    - Параметры: `plugin` (массив данных), `colClass`
    - Использование: Отображение плагина в списке

11. **theme-card.php** - Карточки тем
    - Параметры: `theme` (массив данных), `features`, `supportsCustomization`, `supportsNavigation`, `hasSettings`, `colClass`
    - Использование: Отображение темы в списке

12. **spinner.php** - Спиннер загрузки
    - Параметры: `size` (sm/md/lg), `variant` (border/grow), `text`, `classes`
    - Использование: Индикатор загрузки

13. **table.php** - Таблицы
    - Параметры: `headers`, `rows`, `striped`, `hover`, `bordered`, `classes`
    - Использование: Отображение табличных данных

14. **stats-card.php** - Статистические карточки
    - Параметры: `label`, `value`, `icon`, `color` (primary/success/warning/danger/info), `size` (sm/md/lg/xl), `classes`
    - Использование: Статистика (количество файлов, размер кеша, и т.д.)

### Компоненты layout

15. **header.php** - Шапка сайта
16. **sidebar.php** - Боковая панель
17. **footer.php** - Подвал
18. **page-header.php** - Заголовок страницы
19. **notifications.php** - Система уведомлений
20. **scripts.php** - JavaScript скрипты

## Использование компонентов

### Прямое включение

```php
<?php
// В шаблонах
include __DIR__ . '/../components/button.php';
$text = 'Сохранить';
$type = 'primary';
$icon = 'save';
?>
```

### Через ob_start/ob_get_clean (для строк)

```php
<?php
ob_start();
$text = 'Сохранить';
$type = 'primary';
$icon = 'save';
include __DIR__ . '/../components/button.php';
$buttonHtml = ob_get_clean();
?>
```

### Через ComponentHelper (рекомендуется)

```php
<?php
require_once __DIR__ . '/../../includes/ComponentHelper.php';
includeComponent('button', [
    'text' => 'Сохранить',
    'type' => 'primary',
    'icon' => 'save'
]);
?>
```

## Структура компонентов

Все компоненты находятся в `engine/skins/components/` и могут быть включены в любой шаблон или страницу.

## Рефакторинг шаблонов

Заменено в шаблонах:
- ✅ `alert` → компонент `alert.php` (все шаблоны)
- ✅ `empty-state` → компонент `empty-state.php` (plugins, themes, cache-view, logs-view)
- ✅ Кнопки → компонент `button.php` (plugins, themes, cache-view, logs-view, settings)
- ✅ Модальные окна загрузки → компонент `upload-modal.php` (plugins, themes)
- ✅ Карточки плагинов → компонент `plugin-card.php` (plugins.php)
- ✅ Карточки тем → компонент `theme-card.php` (themes.php)
- ✅ Content sections → компонент `content-section.php` (plugins, themes)
- ✅ Статистические карточки → компонент `stats-card.php` (cache-view.php)
- ✅ Спиннеры → компонент `spinner.php` (готов к использованию)

## Преимущества

1. **Переиспользование** - один компонент используется в разных местах
2. **Единообразие** - все кнопки, алерты, карточки выглядят одинаково
3. **Легкость изменений** - изменили компонент → изменилось везде
4. **Меньше кода** - меньше дублирования
5. **Проще тестировать** - компоненты независимы

