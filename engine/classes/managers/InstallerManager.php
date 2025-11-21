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
        'webhooks',
        'roles',
        'permissions',
        'role_permissions'
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
            
            // После создания таблиц ролей выполняем SQL для создания базовых ролей и разрешений
            if (empty($errors)) {
                try {
                    $rolesSqlFile = dirname(__DIR__) . '/db/roles_permissions.sql';
                    if (file_exists($rolesSqlFile)) {
                        $rolesSql = file_get_contents($rolesSqlFile);
                        if (!empty($rolesSql)) {
                            // Выполняем SQL по частям, пропуская CREATE TABLE (они уже созданы)
                            $statements = array_filter(
                                array_map('trim', explode(';', $rolesSql)),
                                fn($stmt) => !empty($stmt) && 
                                    !preg_match('/^--/', $stmt) && 
                                    !preg_match('/^\/\*/', $stmt) &&
                                    stripos($stmt, 'CREATE TABLE') === false
                            );
                            
                            foreach ($statements as $statement) {
                                // Пропускаем комментарии
                                $statement = preg_replace('/--.*$/m', '', $statement);
                                $statement = preg_replace('/\/\*.*?\*\//s', '', $statement);
                                $statement = trim($statement);
                                
                                if (!empty($statement)) {
                                    try {
                                        $db->exec($statement);
                                    } catch (Exception $e) {
                                        // Игнорируем ошибки типа "уже существует" (INSERT IGNORE)
                                        if (stripos($e->getMessage(), 'Duplicate') === false && 
                                            stripos($e->getMessage(), 'already exists') === false) {
                                            error_log("Roles SQL error: " . $e->getMessage());
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error loading roles SQL: " . $e->getMessage());
                    // Не добавляем в errors, так как это не критично
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
     * Получение определений таблиц с учетом версии MySQL и кодировки
     * 
     * @param string $charset Кодировка (по умолчанию utf8mb4)
     * @param string $collation Коллация (по умолчанию utf8mb4_unicode_ci)
     * @return array
     */
    public function getTableDefinitions(string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): array {
        $isMySQL8Plus = $this->isMySQL8Plus();
        
        // Базовые определения таблиц (совместимы с MySQL 5.7 и 8.0+)
        return $this->getTableDefinitionsForVersion($isMySQL8Plus, $charset, $collation);
    }
    
    /**
     * Получение определений таблиц для конкретной версии MySQL и кодировки
     * 
     * @param bool $isMySQL8Plus Использовать оптимизации для MySQL 8.0+
     * @param string $charset Кодировка
     * @param string $collation Коллация
     * @return array
     */
    private function getTableDefinitionsForVersion(bool $isMySQL8Plus, string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): array {
        // Базовые определения таблиц (совместимы с MySQL 5.7 и 8.0+)
        $tables = [
            'plugins' => "CREATE TABLE IF NOT EXISTS `plugins` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `slug` varchar(100) COLLATE {$collation} NOT NULL,
                `name` varchar(100) COLLATE {$collation} NOT NULL,
                `description` text COLLATE {$collation},
                `version` varchar(20) COLLATE {$collation} DEFAULT NULL,
                `author` varchar(100) COLLATE {$collation} DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '0',
                `settings` text COLLATE {$collation},
                `installed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `idx_plugins_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
            'site_settings' => "CREATE TABLE IF NOT EXISTS `site_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `setting_value` text COLLATE utf8mb4_unicode_ci,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`),
                KEY `idx_settings_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
            'users' => "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `role_ids` JSON DEFAULT NULL COMMENT 'Массив ID ролей пользователя',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                KEY `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
            'roles' => "CREATE TABLE IF NOT EXISTS `roles` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `is_system` TINYINT(1) DEFAULT 0 COMMENT 'Системная роль (нельзя удалить)',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
            'permissions' => "CREATE TABLE IF NOT EXISTS `permissions` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `category` VARCHAR(50) DEFAULT NULL COMMENT 'Категория разрешения (admin, cabinet, plugin, etc.)',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `category` (`category`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
            'role_permissions' => "CREATE TABLE IF NOT EXISTS `role_permissions` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `role_id` INT(11) UNSIGNED NOT NULL,
                `permission_id` INT(11) UNSIGNED NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `role_permission` (`role_id`, `permission_id`),
                KEY `role_id` (`role_id`),
                KEY `permission_id` (`permission_id`),
                CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",
            
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

