<?php
/**
 * Модуль установки системи
 * Перевірка установки та створення таблиць БД
 * 
 * Підтримує MySQL 5.7 та MySQL 8.0+ (пріоритет на версію 8+)
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
     * Ініціалізація модуля
     */
    protected function init(): void {
        // Модуль не потребує ініціалізації
    }
    
    /**
     * Реєстрація хуків модуля
     */
    public function registerHooks(): void {
        // Установщик не реєструє хуки
    }
    
    /**
     * Отримання інформації про модуль
     */
    public function getInfo(): array {
        return [
            'name' => 'InstallerManager',
            'title' => 'Установщик системи',
            'description' => 'Перевірка та виконання установки системи',
            'version' => '1.0.0',
            'author' => 'Flowaxy Team'
        ];
    }
    
    /**
     * Отримання API методів модуля
     */
    public function getApiMethods(): array {
        return [
            'isInstalled' => 'Перевірка установки системи',
            'checkTables' => 'Перевірка існування таблиць',
            'install' => 'Установка системи',
            'getRequiredTables' => 'Отримання списку обов\'язкових таблиць'
        ];
    }
    
    /**
     * Перевірка, чи встановлена система
     * Перевіряє наявність файлу database.ini
     * 
     * @return bool
     */
    public function isInstalled(): bool {
        try {
            $databaseIniFile = dirname(__DIR__) . '/data/database.ini';
            
            // Якщо файл database.ini існує, система встановлена
            return file_exists($databaseIniFile);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Перевірка існування всіх обов'язкових таблиць
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
     * Перевірка існування таблиці
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
     * Установка системи
     * Створює всі необхідні таблиці в БД
     * 
     * @return array
     */
    public function install(): array {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return [
                    'success' => false,
                    'message' => 'Не вдалося підключитися до бази даних',
                    'errors' => []
                ];
            }
            
            $errors = [];
            $tables = $this->getTableDefinitions();
            
            foreach ($tables as $tableName => $sql) {
                try {
                    $db->exec($sql);
                } catch (Exception $e) {
                    $errors[] = "Помилка при створенні таблиці {$tableName}: " . $e->getMessage();
                }
            }
            
            // Після створення таблиць ролей виконуємо SQL для створення базових ролей та дозволів
            if (empty($errors)) {
                try {
                    $rolesSqlFile = dirname(__DIR__) . '/db/roles_permissions.sql';
                    if (file_exists($rolesSqlFile)) {
                        $rolesSql = file_get_contents($rolesSqlFile);
                        if (!empty($rolesSql)) {
                            // Виконуємо SQL частинами, пропускаючи CREATE TABLE (вони вже створені)
                            $statements = array_filter(
                                array_map('trim', explode(';', $rolesSql)),
                                fn($stmt) => !empty($stmt) && 
                                    !preg_match('/^--/', $stmt) && 
                                    !preg_match('/^\/\*/', $stmt) &&
                                    stripos($stmt, 'CREATE TABLE') === false
                            );
                            
                            foreach ($statements as $statement) {
                                // Пропускаємо коментарі
                                $statement = preg_replace('/--.*$/m', '', $statement);
                                $statement = preg_replace('/\/\*.*?\*\//s', '', $statement);
                                $statement = trim($statement);
                                
                                if (!empty($statement)) {
                                    try {
                                        $db->exec($statement);
                                    } catch (Exception $e) {
                                        // Ігноруємо помилки типу "вже існує" (INSERT IGNORE)
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
                    // Не додаємо в errors, оскільки це не критично
                }
            }
            
            return [
                'success' => empty($errors),
                'message' => empty($errors) ? 'Система успішно встановлена' : 'Установка завершена з помилками',
                'errors' => $errors
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Помилка при установці: ' . $e->getMessage(),
                'errors' => []
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Критична помилка: ' . $e->getMessage(),
                'errors' => []
            ];
        }
    }
    
    /**
     * Визначення версії MySQL
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
                // Визначаємо, чи є версія 8.0+
                $versionParts = explode('.', self::$mysqlVersion);
                $majorVersion = (int)($versionParts[0] ?? 0);
                $minorVersion = (int)($versionParts[1] ?? 0);
                
                // MySQL 8.0+ визначається як версія >= 8.0 (пріоритет на 8+)
                self::$isMySQL8Plus = ($majorVersion > 8) || ($majorVersion === 8);
                
                return self::$mysqlVersion;
            }
        } catch (Exception $e) {
            error_log("InstallerManager: Error detecting MySQL version: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Перевірка, чи є MySQL версії 8.0+
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
     * Отримання визначень таблиць з урахуванням версії MySQL та кодування
     * 
     * @param string $charset Кодування (за замовчуванням utf8mb4)
     * @param string $collation Колація (за замовчуванням utf8mb4_unicode_ci)
     * @return array
     */
    public function getTableDefinitions(string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): array {
        $isMySQL8Plus = $this->isMySQL8Plus();
        
        // Базові визначення таблиць (сумісні з MySQL 5.7 та 8.0+)
        return $this->getTableDefinitionsForVersion($isMySQL8Plus, $charset, $collation);
    }
    
    /**
     * Отримання визначень таблиць для конкретної версії MySQL та кодування
     * 
     * @param bool $isMySQL8Plus Використовувати оптимізації для MySQL 8.0+
     * @param string $charset Кодування
     * @param string $collation Колація
     * @return array
     */
    private function getTableDefinitionsForVersion(bool $isMySQL8Plus, string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): array {
        // Базові визначення таблиць (сумісні з MySQL 5.7 та 8.0+)
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
                `session_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Токен сессии для защиты от одновременного входа',
                `last_activity` DATETIME DEFAULT NULL COMMENT 'Время последней активности пользователя',
                `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Статус активности пользователя (1 - активен, 0 - неактивен)',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                KEY `idx_email` (`email`),
                KEY `idx_session_token` (`session_token`),
                KEY `idx_last_activity` (`last_activity`),
                KEY `idx_is_active` (`is_active`)
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
        
        // Для MySQL 8.0+ можна використовувати додаткові оптимізації
        // Пріоритет на версію 8+, але SQL сумісний з MySQL 5.7 та 8.0+
        if ($isMySQL8Plus) {
            // В MySQL 8.0+ можна використовувати:
            // - Функціональні індекси
            // - Покращену підтримку JSON
            // - Оптимізовані запити
            // Але для сумісності залишаємо базовий варіант
        }
        
        return $tables;
    }
    
    /**
     * Отримання списку обов'язкових таблиць
     * 
     * @return array
     */
    public function getRequiredTables(): array {
        return self::REQUIRED_TABLES;
    }
}

