<?php
/**
 * Менеджер налаштувань сайту
 * Централізована робота з налаштуваннями через клас
 * 
 * @package Engine\Classes\Managers
 * @version 1.0.0
 */

declare(strict_types=1);

class SettingsManager {
    private static ?self $instance = null;
    private array $settings = [];
    private bool $loaded = false;
    
    /**
     * Конструктор (приватный для Singleton)
     */
    private function __construct() {
    }
    
    /**
     * Получение экземпляра класса (Singleton)
     * 
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Загрузка всех настроек из БД
     * 
     * @param bool $force Принудительная перезагрузка
     * @return void
     */
    public function load(bool $force = false): void {
        if ($this->loaded && !$force) {
            return;
        }
        
        // Используем кеш
        if (function_exists('cache_remember')) {
            $this->settings = cache_remember('site_settings', function(): array {
                return $this->loadFromDatabase();
            }, 3600);
        } else {
            $this->settings = $this->loadFromDatabase();
        }
        
        $this->loaded = true;
    }
    
    /**
     * Загрузка настроек из БД
     * 
     * @return array
     */
    private function loadFromDatabase(): array {
        try {
            $db = Database::getInstance()->getConnection();
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
            $db = Database::getInstance()->getConnection();
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
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, (string)$value]);
                $this->settings[$key] = (string)$value;
            }
            
            $db->commit();
            
            // Очищаем кеш
            if (function_exists('cache_forget')) {
                cache_forget('site_settings');
            }
            
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
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
            $db = Database::getInstance()->getConnection();
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
    
    // Предотвращение клонирования
    private function __clone() {}
    
    /**
     * @return void
     * @throws Exception
     */
    public function __wakeup(): void {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Глобальная функция для получения экземпляра SettingsManager
 * 
 * @return SettingsManager
 */
function settingsManager(): SettingsManager {
    return SettingsManager::getInstance();
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

