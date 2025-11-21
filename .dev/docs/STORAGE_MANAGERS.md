# Система управления хранилищами данных

Полная система менеджеров для управления cookies, сессиями и клиентским хранилищем (LocalStorage/SessionStorage).

## Архитектура

Система состоит из следующих компонентов:

1. **StorageInterface** - единый интерфейс для всех типов хранилища
2. **CookieManager** - менеджер для работы с cookies
3. **SessionManager** - менеджер для работы с сессиями
4. **StorageManager** - менеджер для работы с LocalStorage/SessionStorage
5. **StorageFactory** - фабрика для создания менеджеров
6. **JavaScript API** - клиентская библиотека для работы с хранилищем

## Использование

### CookieManager

```php
use Engine\Classes\Managers\CookieManager;

// Получение экземпляра
$cookie = CookieManager::getInstance();

// Или через хелпер
$cookie = cookieManager();

// Установка значения
$cookie->set('user_preference', 'dark_mode');

// Установка постоянной cookie (на год)
$cookie->forever('remember_me', true);

// Установка временной cookie (до закрытия браузера)
$cookie->temporary('session_token', 'abc123');

// Получение значения
$preference = $cookie->get('user_preference', 'light_mode');

// Получение JSON
$data = $cookie->getJson('user_data', []);

// Установка JSON
$cookie->setJson('user_data', ['name' => 'John', 'age' => 30]);

// Зашифрованная cookie
$cookie->encrypted('sensitive_data', 'secret_value');

// Получение расшифрованной cookie
$value = $cookie->decrypted('sensitive_data');

// Удаление
$cookie->remove('user_preference');
```

### SessionManager

```php
use Engine\Classes\Managers\SessionManager;

// Получение экземпляра
$session = SessionManager::getInstance();

// Или через хелпер
$session = sessionManager('admin'); // С префиксом 'admin'

// Установка префикса для группировки ключей
$session->setPrefix('plugin_name');

// Установка значения
$session->set('user_id', 123);

// Получение значения
$userId = $session->get('user_id');

// Flash сообщения (читаются один раз)
$session->setFlash('success', 'Данные сохранены');
$message = $session->flash('success'); // После чтения удаляется

// Работа с JSON
$session->setJson('user_data', ['name' => 'John']);
$data = $session->getJson('user_data');

// Увеличение/уменьшение числовых значений
$session->increment('view_count');
$session->decrement('items', 5);

// Получение и удаление (pull)
$value = $session->pull('temp_data');

// Получение всех данных с префиксом
$allData = $session->all(true); // Только с префиксом

// Очистка данных с префиксом
$session->clear(true);
```

### StorageManager (Client Storage)

#### Серверная часть (PHP)

```php
use Engine\Classes\Managers\StorageManager;

// Получение экземпляра
$storage = StorageManager::getInstance();

// Или через хелпер
$storage = storageManager('localStorage', 'plugin_name');

// Установка типа хранилища
$storage->setType('sessionStorage'); // или 'localStorage'

// Установка значения (генерирует JavaScript код)
$storage->set('theme', 'dark');

// Получение сгенерированного JavaScript
$js = $storage->getJavaScript();
// Вывести перед закрытием </body>
echo $js;
```

#### Клиентская часть (JavaScript)

```javascript
// Использование глобального экземпляра
Storage.set('theme', 'dark');
const theme = Storage.get('theme', 'light');

// Создание нового экземпляра
const myStorage = FlowaxyStorage.StorageFactory.localStorage('my_prefix');
myStorage.set('key', 'value');

// Использование sessionStorage
const sessionStorage = FlowaxyStorage.StorageFactory.sessionStorage();
sessionStorage.set('temp_data', 'value');

// Работа с JSON
Storage.setJson('user', {name: 'John', age: 30});
const user = Storage.getJson('user');

// Увеличение/уменьшение
Storage.increment('counter');
Storage.decrement('items', 5);

// Получение всех данных
const all = Storage.all();

// Очистка
Storage.clear(); // Все данные
Storage.clear(true); // Только с префиксом
```

### StorageFactory

```php
use Engine\Classes\Managers\StorageFactory;

// Получение менеджера через фабрику
$cookie = StorageFactory::cookie();
$session = StorageFactory::session('admin');
$localStorage = StorageFactory::localStorage('plugin');
$sessionStorage = StorageFactory::sessionStorage();

// Или через универсальный метод
$storage = StorageFactory::get('session', 'prefix');
```

### Хелпер-функции

```php
// Получение менеджера cookies
$cookie = cookieManager();

// Получение менеджера сессий
$session = sessionManager('prefix');

// Получение менеджера клиентского хранилища
$storage = storageManager('localStorage', 'prefix');

// Универсальная фабрика
$storage = storageFactory('session', 'prefix');
```

## Использование в плагинах

Плагины могут использовать менеджеры хранилища для сохранения своих данных:

```php
// В классе плагина
class MyPlugin extends BasePlugin {
    public function saveSettings(array $settings) {
        // Сохраняем в сессию с префиксом плагина
        $session = sessionManager('my_plugin');
        $session->setMultiple($settings);
        
        // Или в localStorage через StorageManager
        $storage = storageManager('localStorage', 'my_plugin');
        $storage->setMultiple($settings);
        
        // Генерируем JavaScript код
        $js = $storage->getJavaScript();
        // Добавляем в вывод страницы
    }
    
    public function loadSettings(): array {
        $session = sessionManager('my_plugin');
        return $session->all(true);
    }
}
```

## Преимущества

1. **Единый API** - все хранилища имеют одинаковый интерфейс
2. **Префиксы** - изоляция данных по плагинам/модулям
3. **Типобезопасность** - поддержка JSON и автоматическая сериализация
4. **Безопасность** - поддержка шифрования для cookies
5. **Flash сообщения** - встроенная поддержка одноразовых сообщений
6. **JavaScript API** - полная поддержка на клиенте
7. **Удобные функции** - increment/decrement, pull, getMultiple и т.д.

## Файлы

- `engine/classes/storage/StorageInterface.php` - интерфейс
- `engine/classes/managers/CookieManager.php` - менеджер cookies
- `engine/classes/managers/SessionManager.php` - менеджер сессий
- `engine/classes/managers/StorageManager.php` - менеджер клиентского хранилища
- `engine/classes/managers/StorageFactory.php` - фабрика
- `engine/skins/assets/js/storage.js` - JavaScript API
- `engine/includes/functions.php` - хелпер-функции

