<?php
/**
 * Модуль управління налаштуваннями сайту
 * Централізована робота з налаштуваннями через клас
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

class SettingsManager extends BaseModule {
    private array $settings = [];
    private bool $loaded = false;
    
    /**
     * Ініціалізація модуля
     */
    protected function init(): void {
        // Налаштування завантажуються ліниво при першому зверненні
    }
    
    /**
     * Реєстрація хуків модуля
     */
    public function registerHooks(): void {
        // Модуль SettingsManager не реєструє хуки
    }
    
    /**
     * Отримання інформації про модуль
     */
    public function getInfo(): array {
        return [
            'name' => 'SettingsManager',
            'title' => 'Менеджер налаштувань',
            'description' => 'Централізоване управління налаштуваннями сайту',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
        ];
    }
    
    /**
     * Отримання API методів модуля
     */
    public function getApiMethods(): array {
        return [
            'get' => 'Отримання налаштування',
            'set' => 'Збереження налаштування',
            'delete' => 'Видалення налаштування',
            'all' => 'Отримання всіх налаштувань',
            'has' => 'Перевірка існування налаштування'
        ];
    }
    
    /**
     * Завантаження всіх налаштувань з БД
     * 
     * @param bool $force Примусова перезавантаження
     * @return void
     */
    public function load(bool $force = false): void {
        if ($this->loaded && !$force) {
            return;
        }
        
        // Якщо примусова перезавантаження, очищаємо кеш перед завантаженням
        if ($force && function_exists('cache_forget')) {
            cache_forget('site_settings');
        }
        
        // Використовуємо кеш тільки якщо не примусова перезавантаження
        if (!$force && function_exists('cache_remember')) {
            $this->settings = cache_remember('site_settings', function(): array {
                return $this->loadFromDatabase();
            }, 3600);
        } else {
            // Завантажуємо безпосередньо з БД (минаючи кеш)
            $this->settings = $this->loadFromDatabase();
        }
        
        $this->loaded = true;
    }
    
    /**
     * Завантаження налаштувань з БД
     * 
     * @return array
     */
    private function loadFromDatabase(): array {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return [];
            }
            
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            
            if ($stmt === false) {
                return [];
            }
            
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (Exception $e) {
            if (function_exists('logger')) {
                logger()->logError('Failed to load settings from database', ['error' => $e->getMessage()]);
            } else {
                error_log("Error loading site settings: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Получение настройки
     * 
     * @param string $key Ключ настройки
     * @param string $default Значение по умолчанию
     * @return string
     */
    public function get(string $key, string $default = ''): string {
        $this->load();
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Получение всех настроек
     * 
     * @return array
     */
    public function all(): array {
        $this->load();
        return $this->settings;
    }
    
    /**
     * Сохранение настройки
     * 
     * @param string $key Ключ настройки
     * @param string $value Значение
     * @return bool
     */
    public function set(string $key, string $value): bool {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return false;
            }
            
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $result = $stmt->execute([$key, $value]);
            
            if ($result) {
                // Обновляем локальный кеш
                $this->settings[$key] = $value;
                
                // Очищаем кеш
                if (function_exists('cache_forget')) {
                    cache_forget('site_settings');
                }
            }
            
            return $result;
        } catch (Exception $e) {
            if (function_exists('logger')) {
                logger()->logError('Failed to save setting', ['key' => $key, 'error' => $e->getMessage()]);
            } else {
                error_log("Failed to save setting '{$key}': " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Сохранение нескольких настроек
     * 
     * @param array $settings Массив настроек [key => value]
     * @return bool
     */
    public function setMultiple(array $settings): bool {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return false;
            }
            
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, (string)$value]);
                // Обновляем локальный кеш настроек
                $this->settings[$key] = (string)$value;
            }
            
            $db->commit();
            
            // Очищаем кеш Cache перед обновлением локального кеша
            if (function_exists('cache_forget')) {
                cache_forget('site_settings');
            }
            
            // Помечаем, что настройки загружены (чтобы избежать повторной загрузки)
            $this->loaded = true;
            
            return true;
        } catch (Exception $e) {
            $db = $this->getDB();
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            
            if (function_exists('logger')) {
                logger()->logError('Failed to save multiple settings', ['error' => $e->getMessage()]);
            } else {
                error_log("Failed to save multiple settings: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Удаление настройки
     * 
     * @param string $key Ключ настройки
     * @return bool
     */
    public function delete(string $key): bool {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return false;
            }
            
            $stmt = $db->prepare("DELETE FROM site_settings WHERE setting_key = ?");
            $result = $stmt->execute([$key]);
            
            if ($result) {
                unset($this->settings[$key]);
                
                // Очищаем кеш
                if (function_exists('cache_forget')) {
                    cache_forget('site_settings');
                }
            }
            
            return $result;
        } catch (Exception $e) {
            if (function_exists('logger')) {
                logger()->logError('Failed to delete setting', ['key' => $key, 'error' => $e->getMessage()]);
            } else {
                error_log("Failed to delete setting '{$key}': " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Проверка существования настройки
     * 
     * @param string $key Ключ настройки
     * @return bool
     */
    public function has(string $key): bool {
        $this->load();
        return isset($this->settings[$key]);
    }
    
    /**
     * Очистка кеша настроек
     * 
     * @return void
     */
    public function clearCache(): void {
        $this->loaded = false;
        $this->settings = [];
        
        if (function_exists('cache_forget')) {
            cache_forget('site_settings');
        }
    }
    
    /**
     * Перезагрузка настроек
     * 
     * @return void
     */
    public function reloadSettings(): void {
        $this->load(true);
    }
}

/**
 * Глобальная функция для получения экземпляра SettingsManager
 * 
 * @return SettingsManager|null
 */
function settingsManager(): ?SettingsManager {
    // Избегаем рекурсии: проверяем, что SettingsManager не инициализируется
    static $initializing = false;
    if ($initializing) {
        return null;
    }
    
    if (!class_exists('SettingsManager')) {
        return null;
    }
    
    try {
        $initializing = true;
        $instance = SettingsManager::getInstance();
        $initializing = false;
        return $instance;
    } catch (Exception $e) {
        $initializing = false;
        error_log("settingsManager() error: " . $e->getMessage());
        return null;
    } catch (Error $e) {
        $initializing = false;
        error_log("settingsManager() fatal error: " . $e->getMessage());
        return null;
    }
}

/**
 * Глобальная функция для получения настройки сайта
 * 
 * @param string $key Ключ настройки
 * @param string $default Значение по умолчанию
 * @return string
 */
function getSetting(string $key, string $default = ''): string {
    if (class_exists('SettingsManager')) {
        return settingsManager()->get($key, $default);
    }
    return $default;
}

