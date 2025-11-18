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

### Компоненты layout

11. **header.php** - Шапка сайта
12. **sidebar.php** - Боковая панель
13. **footer.php** - Подвал
14. **page-header.php** - Заголовок страницы
15. **notifications.php** - Система уведомлений
16. **scripts.php** - JavaScript скрипты

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
- ✅ `alert` → компонент `alert.php`
- ✅ `empty-state` → компонент `empty-state.php`
- ✅ Кнопки → компонент `button.php`
- ✅ Модальные окна загрузки → компонент `upload-modal.php`
- ✅ Карточки плагинов → компонент `plugin-card.php`
- ✅ Content sections → компонент `content-section.php`

## Преимущества

1. **Переиспользование** - один компонент используется в разных местах
2. **Единообразие** - все кнопки, алерты, карточки выглядят одинаково
3. **Легкость изменений** - изменили компонент → изменилось везде
4. **Меньше кода** - меньше дублирования
5. **Проще тестировать** - компоненты независимы

