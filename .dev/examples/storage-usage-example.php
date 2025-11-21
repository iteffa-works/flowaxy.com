<?php
/**
 * Примеры использования менеджеров хранилища
 * 
 * Демонстрация работы с CookieManager, SessionManager и StorageManager
 */

// ============================================================================
// 1. COOKIE MANAGER
// ============================================================================

// Получение менеджера cookies
$cookie = cookieManager();

// Установка простого значения
$cookie->set('theme', 'dark');

// Установка постоянной cookie (на год)
$cookie->forever('remember_me', true);

// Установка временной cookie (до закрытия браузера)
$cookie->temporary('session_token', 'abc123');

// Установка JSON данных
$cookie->setJson('user_preferences', [
    'theme' => 'dark',
    'language' => 'uk',
    'notifications' => true
]);

// Получение значения
$theme = $cookie->get('theme', 'light');

// Получение JSON
$prefs = $cookie->getJson('user_preferences', []);

// Зашифрованная cookie
$cookie->encrypted('sensitive_data', 'secret_value');
$secret = $cookie->decrypted('sensitive_data');

// Удаление
$cookie->remove('theme');

// ============================================================================
// 2. SESSION MANAGER
// ============================================================================

// Получение менеджера сессий с префиксом (для изоляции данных плагина)
$session = sessionManager('my_plugin');

// Установка значения
$session->set('user_id', 123);

// Получение значения
$userId = $session->get('user_id');

// Flash сообщения (читаются один раз)
$session->setFlash('success', 'Данные успешно сохранены');
$message = $session->flash('success'); // После чтения автоматически удаляется

// Работа с JSON
$session->setJson('user_data', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
$userData = $session->getJson('user_data');

// Увеличение/уменьшение числовых значений
$session->increment('view_count');        // +1
$session->increment('items', 5);          // +5
$session->decrement('points', 10);        // -10

// Получение и удаление (pull)
$tempData = $session->pull('temp_data');  // Получает и удаляет

// Получение всех данных с префиксом
$allData = $session->all(true);

// Очистка только данных с префиксом
$session->clear(true);

// ============================================================================
// 3. STORAGE MANAGER (LocalStorage/SessionStorage)
// ============================================================================

// Получение менеджера localStorage
$storage = storageManager('localStorage', 'my_plugin');

// Установка значения (генерирует JavaScript код)
$storage->set('theme', 'dark');
$storage->set('user_settings', ['notifications' => true]);

// Получение сгенерированного JavaScript кода
$jsCode = $storage->getJavaScript();
// Вывести перед закрытием </body>
// echo $jsCode;

// Работа с sessionStorage
$sessionStorage = storageManager('sessionStorage', 'temp');

// ============================================================================
// 4. STORAGE FACTORY
// ============================================================================

// Универсальное получение менеджера через фабрику
$cookie = StorageFactory::cookie();
$session = StorageFactory::session('admin');
$localStorage = StorageFactory::localStorage('plugin');
$sessionStorage = StorageFactory::sessionStorage();

// Или через универсальный метод
$storage = StorageFactory::get('session', 'prefix');

// ============================================================================
// 5. ИСПОЛЬЗОВАНИЕ В ПЛАГИНАХ
// ============================================================================

class MyPlugin extends BasePlugin {
    
    public function saveSettings(array $settings) {
        // Сохраняем настройки в сессию с префиксом плагина
        $session = sessionManager($this->getSlug());
        $session->setMultiple($settings);
        
        // Или в localStorage для клиентской части
        $storage = storageManager('localStorage', $this->getSlug());
        $storage->setMultiple($settings);
        
        // Генерируем JavaScript код
        $js = $storage->getJavaScript();
        // Добавляем в вывод страницы через хук
        addHook('admin_footer', function() use ($js) {
            echo $js;
        });
    }
    
    public function loadSettings(): array {
        // Загружаем настройки из сессии
        $session = sessionManager($this->getSlug());
        return $session->all(true);
    }
    
    public function deleteSettings() {
        // Удаляем все настройки плагина
        $session = sessionManager($this->getSlug());
        $session->clear(true);
    }
}

// ============================================================================
// 6. ИСПОЛЬЗОВАНИЕ В АДМИНКЕ
// ============================================================================

// В админ-странице
class SettingsPage extends AdminPage {
    
    public function handle() {
        if (Request::getMethod() === 'POST') {
            $settings = [
                'theme' => Request::post('theme', 'light'),
                'language' => Request::post('language', 'uk')
            ];
            
            // Сохраняем в сессию для текущей сессии
            $session = sessionManager('admin_preferences');
            $session->setMultiple($settings);
            
            // Или в localStorage для постоянного хранения
            $storage = storageManager('localStorage', 'admin_preferences');
            $storage->setMultiple($settings);
            
            // Сохраняем JavaScript код для вывода
            $this->addStorageJavaScript($storage->getJavaScript());
            
            $this->setMessage('Настройки сохранены', 'success');
        }
        
        $this->render();
    }
    
    private function addStorageJavaScript(string $js): void {
        // Добавляем JavaScript код для вывода в футере
        if (!isset($GLOBALS['_admin_storage_js'])) {
            $GLOBALS['_admin_storage_js'] = [];
        }
        $GLOBALS['_admin_storage_js'][] = $js;
    }
}

// ============================================================================
// 7. JAVASCRIPT API (на клиенте)
// ============================================================================

/*
// Использование в JavaScript коде на странице

// Глобальный экземпляр с префиксом 'flowaxy'
Storage.set('theme', 'dark');
const theme = Storage.get('theme', 'light');

// Создание нового экземпляра с префиксом
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
Storage.clear();        // Все данные
Storage.clear(true);    // Только с префиксом

// Работа с несколькими значениями
Storage.setMultiple({key1: 'value1', key2: 'value2'});
const values = Storage.getMultiple(['key1', 'key2']);
Storage.removeMultiple(['key1', 'key2']);
*/

