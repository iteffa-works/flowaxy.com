<?php
/**
 * Централизованная система конфигурации параметров системы
 * Управление всеми параметрами через SettingsManager
 * 
 * @package Engine\Classes\Data
 * @version 1.0.0
 */

declare(strict_types=1);

class SystemConfig {
    private static ?self $instance = null;
    private array $cache = [];
    
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
     * Получение параметра с кешированием
     * 
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    private function getParam(string $key, $default = null) {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        if (class_exists('SettingsManager') && function_exists('settingsManager')) {
            $settings = settingsManager();
            if ($settings !== null) {
                $value = $settings->get($key, (string)$default);
                // Преобразуем строку в нужный тип
                $this->cache[$key] = $this->convertValue($value, $default);
                return $this->cache[$key];
            }
        }
        
        $this->cache[$key] = $default;
        return $default;
    }
    
    /**
     * Преобразование значения в нужный тип
     * 
     * @param mixed $value Значение
     * @param mixed $default Значение по умолчанию (для определения типа)
     * @return mixed
     */
    private function convertValue($value, $default) {
        if ($value === '' || $value === null) {
            return $default;
        }
        
        // Определяем тип по умолчанию
        if (is_int($default)) {
            return (int)$value;
        } elseif (is_float($default)) {
            return (float)$value;
        } elseif (is_bool($default)) {
            return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
        }
        
        return $value;
    }
    
    /**
     * Очистка кеша
     * 
     * @return void
     */
    public function clearCache(): void {
        $this->cache = [];
    }
    
    // ========== Параметры сессий ==========
    
    /**
     * Время жизни сессии (в секундах)
     * 
     * @return int
     */
    public function getSessionLifetime(): int {
        return $this->getParam('session_lifetime', 7200); // 2 часа по умолчанию
    }
    
    /**
     * Имя сессии
     * 
     * @return string
     */
    public function getSessionName(): string {
        return $this->getParam('session_name', 'PHPSESSID');
    }
    
    // ========== Параметры базы данных ==========
    
    /**
     * Таймаут подключения к БД (в секундах)
     * 
     * @return int
     */
    public function getDbConnectionTimeout(): int {
        return $this->getParam('db_connection_timeout', 3);
    }
    
    /**
     * Максимальное количество попыток подключения к БД
     * 
     * @return int
     */
    public function getDbMaxAttempts(): int {
        return $this->getParam('db_max_attempts', 3);
    }
    
    /**
     * Таймаут проверки хоста БД (в секундах)
     * 
     * @return int
     */
    public function getDbHostCheckTimeout(): int {
        return $this->getParam('db_host_check_timeout', 1);
    }
    
    /**
     * Порог медленных запросов (в секундах)
     * 
     * @return float
     */
    public function getDbSlowQueryThreshold(): float {
        return (float)$this->getParam('db_slow_query_threshold', 1.0);
    }
    
    // ========== Параметры кеша ==========
    
    /**
     * Включен ли кеш
     * 
     * @return bool
     */
    public function isCacheEnabled(): bool {
        return $this->getParam('cache_enabled', true);
    }
    
    /**
     * Время жизни кеша по умолчанию (в секундах)
     * 
     * @return int
     */
    public function getCacheDefaultTtl(): int {
        return $this->getParam('cache_default_ttl', 3600); // 1 час
    }
    
    /**
     * Автоматическая очистка кеша
     * 
     * @return bool
     */
    public function isCacheAutoCleanup(): bool {
        return $this->getParam('cache_auto_cleanup', true);
    }
    
    // ========== Параметры логирования ==========
    
    /**
     * Включено ли логирование
     * 
     * @return bool
     */
    public function isLoggingEnabled(): bool {
        return $this->getParam('logging_enabled', true);
    }
    
    /**
     * Минимальный уровень логирования
     * 
     * @return string
     */
    public function getLoggingLevel(): string {
        return $this->getParam('logging_level', 'INFO');
    }
    
    /**
     * Максимальный размер файла лога (в байтах)
     * 
     * @return int
     */
    public function getLoggingMaxFileSize(): int {
        return $this->getParam('logging_max_file_size', 10485760); // 10 MB
    }
    
    /**
     * Количество дней хранения логов
     * 
     * @return int
     */
    public function getLoggingRetentionDays(): int {
        return $this->getParam('logging_retention_days', 30);
    }
    
    // ========== Параметры загрузки файлов ==========
    
    /**
     * Максимальный размер загружаемого файла (в байтах)
     * 
     * @return int
     */
    public function getUploadMaxFileSize(): int {
        return $this->getParam('upload_max_file_size', 10485760); // 10 MB
    }
    
    /**
     * Разрешенные расширения файлов
     * 
     * @return array
     */
    public function getUploadAllowedExtensions(): array {
        $extensions = $this->getParam('upload_allowed_extensions', 'jpg,jpeg,png,gif,pdf,doc,docx,zip');
        if (is_string($extensions)) {
            return array_map('trim', explode(',', $extensions));
        }
        return is_array($extensions) ? $extensions : [];
    }
    
    /**
     * Разрешенные MIME типы
     * 
     * @return array
     */
    public function getUploadAllowedMimeTypes(): array {
        $mimeTypes = $this->getParam('upload_allowed_mime_types', 'image/jpeg,image/png,image/gif,application/pdf');
        if (is_string($mimeTypes)) {
            return array_map('trim', explode(',', $mimeTypes));
        }
        return is_array($mimeTypes) ? $mimeTypes : [];
    }
    
    // ========== Параметры безопасности ==========
    
    /**
     * Минимальная длина пароля
     * 
     * @return int
     */
    public function getPasswordMinLength(): int {
        return $this->getParam('password_min_length', 8);
    }
    
    /**
     * Время жизни CSRF токена (в секундах)
     * 
     * @return int
     */
    public function getCsrfTokenLifetime(): int {
        return $this->getParam('csrf_token_lifetime', 3600); // 1 час
    }
    
    // ========== Параметры производительности ==========
    
    /**
     * Включена ли оптимизация запросов
     * 
     * @return bool
     */
    public function isQueryOptimizationEnabled(): bool {
        return $this->getParam('query_optimization_enabled', true);
    }
    
    /**
     * Максимальное количество запросов в секунду
     * 
     * @return int
     */
    public function getMaxQueriesPerSecond(): int {
        return $this->getParam('max_queries_per_second', 100);
    }
    
    // Запобігання клонуванню та десеріалізації
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
 * Глобальная функция для получения экземпляра SystemConfig
 * 
 * @return SystemConfig
 */
function systemConfig(): SystemConfig {
    return SystemConfig::getInstance();
}

