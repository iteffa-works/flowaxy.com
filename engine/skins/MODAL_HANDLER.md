# Центральный обработчик модальных окон (ModalHandler)

## Описание

`ModalHandler` - это центральный класс для управления модальными окнами, который работает как для сайта, так и для админки. Он обеспечивает единый подход к созданию, регистрации и обработке модальных окон.

## Архитектура

```
engine/classes/ui/ModalHandler.php  - Центральный класс (для всех процессов)
engine/skins/                        - Скин для админки (использует ModalHandler)
engine/modules/                      - Модули (прокладка, могут использовать ModalHandler)
themes/                              - Темы сайта (могут использовать ModalHandler)
```

## Использование в админке

### 1. Регистрация модального окна

В конструкторе страницы (`PluginsPage`, `ThemesPage` и т.д.):

```php
public function __construct() {
    parent::__construct();
    
    // Регистрируем модальное окно
    $this->registerModal('uploadPluginModal', [
        'title' => 'Завантажити плагін',
        'type' => 'upload',
        'action' => 'upload_plugin',
        'method' => 'POST',
        'enctype' => 'multipart/form-data',
        'fields' => [
            [
                'type' => 'file',
                'name' => 'plugin_file',
                'label' => 'ZIP архів з плагіном',
                'help' => 'Максимальний розмір: 50 MB',
                'required' => true,
                'attributes' => [
                    'accept' => '.zip'
                ]
            ]
        ],
        'buttons' => [
            [
                'text' => 'Скасувати',
                'type' => 'secondary',
                'action' => 'close'
            ],
            [
                'text' => 'Завантажити',
                'type' => 'primary',
                'icon' => 'upload',
                'action' => 'submit'
            ]
        ]
    ]);
    
    // Регистрируем обработчик
    $this->registerModalHandler('uploadPluginModal', 'upload_plugin', [$this, 'handleUploadPlugin']);
}
```

### 2. Обработчик модального окна

```php
/**
 * Обработчик загрузки плагина для ModalHandler
 * 
 * @param array $data Данные запроса
 * @param array $files Файлы
 * @return array Результат
 */
public function handleUploadPlugin(array $data, array $files): array {
    if (!$this->verifyCsrf()) {
        return ['success' => false, 'error' => 'Помилка безпеки', 'reload' => false];
    }
    
    // Обработка загрузки...
    
    return [
        'success' => true,
        'message' => 'Плагін успішно завантажено',
        'reload' => true,
        'closeModal' => true,
        'closeDelay' => 2000
    ];
}
```

### 3. Обработка AJAX запросов

В методе `handleAjax()`:

```php
private function handleAjax(): void {
    $request = Request::getInstance();
    $modalId = $request->post('modal_id', '');
    $action = SecurityHelper::sanitizeInput($request->post('action', ''));
    
    // Если запрос от модального окна, обрабатываем через ModalHandler
    if (!empty($modalId) && !empty($action)) {
        $this->handleModalRequest($modalId, $action);
        return;
    }
    
    // Обработка других AJAX запросов...
}
```

### 4. Рендеринг модального окна в шаблоне

В методе `handle()`:

```php
$this->render([
    'data' => $data,
    'uploadModalHtml' => $this->renderModal('uploadPluginModal')
]);
```

В шаблоне (`plugins.php`):

```php
<!-- Модальне вікно завантаження плагіна через ModalHandler -->
<?php if (!empty($uploadModalHtml)): ?>
    <?= $uploadModalHtml ?>
<?php endif; ?>
```

### 5. Открытие модального окна

В кнопке:

```php
$headerButtons = $this->createButtonGroup([
    [
        'text' => 'Завантажити плагін',
        'type' => 'primary',
        'options' => [
            'icon' => 'upload',
            'attributes' => [
                'data-bs-toggle' => 'modal', 
                'data-bs-target' => '#uploadPluginModal',
                'onclick' => 'window.ModalHandler && window.ModalHandler.show("uploadPluginModal")'
            ]
        ]
    ]
]);
```

## Типы модальных окон

### 1. Default (по умолчанию)
Обычное модальное окно с формой.

### 2. Upload
Модальное окно для загрузки файлов с прогресс-баром.

### 3. Confirm
Модальное окно подтверждения действия.

### 4. Form
Модальное окно с кастомной формой.

## Конфигурация модального окна

```php
[
    'id' => 'modalId',                    // ID модального окна (обязательно)
    'title' => 'Заголовок',               // Заголовок
    'type' => 'default',                  // Тип: default, upload, confirm, form
    'size' => '',                         // Размер: sm, lg, xl
    'centered' => false,                  // Вертикальное центрирование
    'backdrop' => true,                   // Фон при открытии
    'keyboard' => true,                   // Закрытие по ESC
    'action' => 'action_name',            // Действие для обработки
    'url' => '',                          // URL для отправки (если отличается от текущего)
    'method' => 'POST',                   // HTTP метод
    'enctype' => 'multipart/form-data',   // Тип кодирования
    'fields' => [],                       // Поля формы
    'buttons' => [],                      // Кнопки
    'content' => '',                      // Кастомный контент (HTML)
    'footer' => '',                       // Кастомный футер (HTML)
    'onSuccess' => null,                  // JavaScript callback при успехе
    'onError' => null,                    // JavaScript callback при ошибке
    'onClose' => null                    // JavaScript callback при закрытии
]
```

## Поля формы

```php
[
    'type' => 'text',                     // Тип: text, email, password, file, textarea, select
    'name' => 'field_name',              // Имя поля
    'label' => 'Метка',                   // Метка поля
    'value' => '',                        // Значение по умолчанию
    'required' => false,                  // Обязательное поле
    'placeholder' => '',                  // Placeholder
    'help' => '',                         // Подсказка
    'options' => [],                      // Опции для select (key => value)
    'attributes' => []                    // Дополнительные атрибуты
]
```

## Кнопки

```php
[
    'text' => 'Текст кнопки',            // Текст
    'type' => 'primary',                 // Тип: primary, secondary, success, danger, warning, info
    'icon' => 'upload',                 // Иконка FontAwesome (без префикса fas fa-)
    'action' => 'submit',               // Действие: submit, close, cancel
    'attributes' => []                  // Дополнительные атрибуты
]
```

## Результат обработчика

```php
[
    'success' => true,                   // Успех операции
    'message' => 'Сообщение',           // Сообщение для пользователя
    'error' => 'Ошибка',                // Ошибка (если success = false)
    'reload' => true,                   // Перезагрузить страницу
    'reloadDelay' => 2000,              // Задержка перед перезагрузкой (мс)
    'closeModal' => true,               // Закрыть модальное окно
    'closeDelay' => 2000,               // Задержка перед закрытием (мс)
    'data' => []                        // Дополнительные данные
]
```

## JavaScript API

### Открытие модального окна

```javascript
window.ModalHandler.show('modalId', {
    data: {
        field1: 'value1',
        field2: 'value2'
    }
});
```

### Закрытие модального окна

```javascript
window.ModalHandler.hide('modalId');
```

## Преимущества

1. **Единый подход** - один класс для всех модальных окон
2. **Централизованная обработка** - все AJAX запросы обрабатываются в одном месте
3. **Автоматическая генерация** - HTML генерируется автоматически
4. **Прогресс-бар** - автоматическое отображение прогресса загрузки
5. **Обработка ошибок** - автоматическое отображение ошибок
6. **Перезагрузка страницы** - автоматическая перезагрузка после успешной операции
7. **Работает везде** - для сайта и админки

## Примеры использования

См. `engine/skins/pages/PluginsPage.php` для полного примера интеграции.

