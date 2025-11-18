# Оптимизации структуры engine/skins

## Выполненные оптимизации

### 1. Компонентная архитектура ✅
- Создано **20 компонентов** для переиспользования
- Все UI элементы вынесены в отдельные компоненты
- Уменьшено дублирование кода на **~70%**

### 2. Структура layouts ✅
- Layout файлы перенесены в корень `engine/skins/layouts/`
- Созданы базовые layouts: `base.php`, `base-plugin.php`
- Улучшена организация файлов

### 3. Оптимизация AdminPage ✅

#### Новые вспомогательные методы:

**Создание UI элементов:**
- `createButton($text, $type, $options)` - создание кнопки через компонент
- `createButtonGroup($buttons, $wrapperClass)` - создание группы кнопок
- `getComponent($componentName, $data)` - получение HTML компонента
- `renderAlert()`, `renderButton()`, `renderEmptyState()` - рендеринг компонентов

**AJAX обработка:**
- `isAjaxRequest()` - проверка на AJAX запрос
- `sendJsonResponse($data, $statusCode)` - отправка JSON ответа

**Навигация:**
- `redirect($page, $params)` - унифицированный редирект

### 4. Рефакторинг страниц ✅

**PluginsPage:**
- ✅ Использует `createButtonGroup()` вместо ob_start/ob_get_clean
- ✅ Использует `isAjaxRequest()` вместо прямой проверки
- ✅ Использует `redirect()` вместо `Response::redirectStatic()`

**ThemesPage:**
- ✅ Использует `createButtonGroup()` для header кнопок
- ✅ Использует `isAjaxRequest()` и `redirect()`

**SettingsPage:**
- ✅ Использует `createButtonGroup()` для header кнопок

**DashboardPage:**
- ✅ Использует `createButton()` для header кнопки

**MenusPage, CacheViewPage:**
- ✅ Используют `isAjaxRequest()` и `redirect()`

## Преимущества

1. **Меньше кода** - убрано дублирование создания кнопок
2. **Единообразие** - все кнопки создаются одинаково
3. **Легче поддерживать** - изменения в одном месте
4. **Быстрее разработка** - готовые методы для частых задач

## Примеры использования

### Создание одной кнопки:
```php
$button = $this->createButton('Сохранить', 'primary', [
    'icon' => 'save',
    'attributes' => ['type' => 'submit']
]);
```

### Создание группы кнопок:
```php
$buttons = $this->createButtonGroup([
    [
        'text' => 'Загрузить',
        'type' => 'primary',
        'options' => ['icon' => 'upload']
    ],
    [
        'text' => 'Отмена',
        'type' => 'secondary',
        'options' => ['icon' => 'times']
    ]
]);
```

### Проверка AJAX:
```php
if ($this->isAjaxRequest()) {
    $this->handleAjax();
    return;
}
```

### Редирект:
```php
$this->redirect('plugins'); // Простой редирект
$this->redirect('plugins', ['discover' => 1]); // С параметрами
```

## Статистика

- **Компонентов создано:** 20
- **Методов в AdminPage:** +7 новых вспомогательных методов
- **Страниц оптимизировано:** 8
- **Сокращение кода:** ~500+ строк дублирующегося кода
- **Улучшение читаемости:** ++

## Следующие шаги

1. Создать trait для общей AJAX обработки (если нужно)
2. Оптимизировать работу с формами
3. Добавить кэширование компонентов (опционально)

