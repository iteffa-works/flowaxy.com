<?php
/**
 * Модуль установки системы
 * Проверка установки и создание таблиц БД
 * 
 * Поддерживает MySQL 5.7 и MySQL 8.0+ (приоритет на версию 8+)
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

class InstallerManager extends BaseModule {
    private const REQUIRED_TABLES = [
        'users',
        'site_settings',
        'plugins',
        'plugin_settings',
        'theme_settings',
        'api_keys',
        'webhooks'
    ];
    
    private static ?string $mysqlVersion = null;
    private static ?bool $isMySQL8Plus = null;
    
    /**
     * Инициализация модуля
     */
    protected function init(): void {
        // Модуль не требует инициализации
    }
    
    /**
     * Регистрация хуков модуля
     */
    public function registerHooks(): void {
        // Установщик не регистрирует хуки
    }
    
    /**
     * Получение информации о модуле
     */
    public function getInfo(): array {
        return [
            'name' => 'InstallerManager',
            'title' => 'Установщик системы',
            'description' => 'Проверка и выполнение установки системы',
            'version' => '1.0.0',
            'author' => 'Flowaxy Team'
        ];
    }
    
    /**
     * Получение API методов модуля
     */
    public function getApiMethods(): array {
        return [
            'isInstalled' => 'Проверка установки системы',
            'checkTables' => 'Проверка существования таблиц',
            'install' => 'Установка системы',
            'getRequiredTables' => 'Получение списка обязательных таблиц'
        ];
    }
    
    /**
     * Проверка установлена ли система
     * Проверяет наличие файла database.ini
     * 
     * @return bool
     */
    public function isInstalled(): bool {
        try {
            $databaseIniFile = dirname(__DIR__) . '/data/database.ini';
            
            // Если файл database.ini существует, система установлена
            return file_exists($databaseIniFile);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Проверка существования всех обязательных таблиц
     * 
     * @return bool
     */
    public function checkTables(): bool {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return false;
            }
            
            foreach (self::REQUIRED_TABLES as $table) {
                if (!$this->tableExists($table)) {
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Проверка существования таблицы
     * 
     * @param string $tableName
     * @return bool
     */
    private function tableExists(string $tableName): bool {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return false;
            }
            
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Установка системы
     * Создает все необходимые таблицы в БД
     * 
     * @return array
     */
    public function install(): array {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return [
                    'success' => false,
                    'message' => 'Не удалось подключиться к базе данных',
                    'errors' => []
                ];
            }
            
            $errors = [];
            $tables = $this->getTableDefinitions();
            
            foreach ($tables as $tableName => $sql) {
                try {
                    $db->exec($sql);
                } catch (Exception $e) {
                    $errors[] = "Ошибка при создании таблицы {$tableName}: " . $e->getMessage();
                }
            }
            
            return [
                'success' => empty($errors),
                'message' => empty($errors) ? 'Система успешно установлена' : 'Установка завершена с ошибками',
                'errors' => $errors
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при установке: ' . $e->getMessage(),
                'errors' => []
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Критическая ошибка: ' . $e->getMessage(),
                'errors' => []
            ];
        }
    }
    
    /**
     * Определение версии MySQL
     * 
     * @return string|null
     */
    private function getMySQLVersion(): ?string {
        if (self::$mysqlVersion !== null) {
            return self::$mysqlVersion;
        }
        
        try {
            $db = $this->getDB();
            if ($db === null) {
                return null;
            }
            
            $stmt = $db->query("SELECT VERSION() as version");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['version'])) {
                self::$mysqlVersion = $result['version'];
                // Определяем, является ли версия 8.0+
                $versionParts = explode('.', self::$mysqlVersion);
                $majorVersion = (int)($versionParts[0] ?? 0);
                $minorVersion = (int)($versionParts[1] ?? 0);
                
                // MySQL 8.0+ определяется как версия >= 8.0 (приоритет на 8+)
                self::$isMySQL8Plus = ($majorVersion > 8) || ($majorVersion === 8);
                
                return self::$mysqlVersion;
            }
        } catch (Exception $e) {
            error_log("InstallerManager: Error detecting MySQL version: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Проверка, является ли MySQL версии 8.0+
     * 
     * @return bool
     */
    private function isMySQL8Plus(): bool {
        if (self::$isMySQL8Plus !== null) {
            return self::$isMySQL8Plus;
        }
        
        $this->getMySQLVersion();
        return self::$isMySQL8Plus ?? false;
    }
    
    /**
     * Получение определений таблиц с учетом версии MySQL
     * 
     * @return array
     */
    public function getTableDefinitions(): array {
        $isMySQL8Plus = $this->isMySQL8Plus();
        
        // Базовые определения таблиц (совместимы с MySQL 5.7 и 8.0+)
        return $this->getTableDefinitionsForVersion($isMySQL8Plus);
    }
    
    /**
     * Получение определений таблиц для конкретной версии MySQL
     * 
     * @param bool $isMySQL8Plus Использовать оптимизации для MySQL 8.0+
     * @return array
     */
    private function getTableDefinitionsForVersion(bool $isMySQL8Plus): array {
        // Базовые определения таблиц (совместимы с MySQL 5.7 и 8.0+)
        $tables = [
            'plugins' => "CREATE TABLE IF NOT EXISTS `plugins` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `description` text COLLATE utf8mb4_unicode_ci,
                `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '0',
                `settings` text COLLATE utf8mb4_unicode_ci,
                `installed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `idx_plugins_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'plugin_settings' => "CREATE TABLE IF NOT EXISTS `plugin_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `plugin_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `setting_value` text COLLATE utf8mb4_unicode_ci,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `plugin_setting` (`plugin_slug`,`setting_key`),
                KEY `idx_plugin_slug` (`plugin_slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'site_settings' => "CREATE TABLE IF NOT EXISTS `site_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `setting_value` text COLLATE utf8mb4_unicode_ci,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`),
                KEY `idx_settings_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'theme_settings' => "CREATE TABLE IF NOT EXISTS `theme_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `theme_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `setting_value` text COLLATE utf8mb4_unicode_ci,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `theme_setting` (`theme_slug`,`setting_key`),
                KEY `idx_theme_slug` (`theme_slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'users' => "CREATE TABLE IF NOT EXISTS `users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                KEY `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'api_keys' => "CREATE TABLE IF NOT EXISTS `api_keys` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL COMMENT 'Название API ключа',
                `key_hash` VARCHAR(255) NOT NULL COMMENT 'Хеш API ключа',
                `key_preview` VARCHAR(20) NOT NULL COMMENT 'Первые 4 символа ключа для отображения',
                `permissions` TEXT DEFAULT NULL COMMENT 'JSON массив разрешений',
                `last_used_at` DATETIME DEFAULT NULL COMMENT 'Последнее использование',
                `expires_at` DATETIME DEFAULT NULL COMMENT 'Срок действия',
                `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активен ли ключ',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `key_hash` (`key_hash`),
                KEY `idx_key_hash` (`key_hash`),
                KEY `idx_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'webhooks' => "CREATE TABLE IF NOT EXISTS `webhooks` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL COMMENT 'Название webhook',
                `url` TEXT NOT NULL COMMENT 'URL для отправки',
                `secret` VARCHAR(255) DEFAULT NULL COMMENT 'Секретный ключ для подписи',
                `events` TEXT DEFAULT NULL COMMENT 'JSON массив событий для отслеживания',
                `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активен ли webhook',
                `last_triggered_at` DATETIME DEFAULT NULL COMMENT 'Последний вызов',
                `success_count` INT UNSIGNED DEFAULT 0 COMMENT 'Количество успешных отправок',
                `failure_count` INT UNSIGNED DEFAULT 0 COMMENT 'Количество неудачных отправок',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        // Для MySQL 8.0+ можно использовать дополнительные оптимизации
        // Приоритет на версию 8+, но SQL совместим с MySQL 5.7 и 8.0+
        if ($isMySQL8Plus) {
            // В MySQL 8.0+ можно использовать:
            // - Функциональные индексы
            // - Улучшенную поддержку JSON
            // - Оптимизированные запросы
            // Но для совместимости оставляем базовый вариант
        }
        
        return $tables;
    }
    
    /**
     * Получение списка обязательных таблиц
     * 
     * @return array
     */
    public function getRequiredTables(): array {
        return self::REQUIRED_TABLES;
    }
}

