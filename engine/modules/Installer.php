<?php
/**
 * Модуль установки системы
 * Проверка установки и создание таблиц БД
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

declare(strict_types=1);

class Installer extends BaseModule {
    private const REQUIRED_TABLES = [
        'users',
        'site_settings',
        'plugins',
        'plugin_settings',
        'theme_settings'
    ];
    
    private const INSTALL_FLAG_KEY = 'system_installed';
    
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
            'name' => 'Installer',
            'title' => 'Установщик системы',
            'description' => 'Проверка и выполнение установки системы',
            'version' => '1.0.0',
            'author' => 'Flowaxy CMS'
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
     * 
     * @return bool
     */
    public function isInstalled(): bool {
        try {
            // Сначала проверяем подключение к БД
            if (!DatabaseHelper::isAvailable(false)) {
                return false;
            }
            
            $db = $this->getDB();
            if ($db === null) {
                return false;
            }
            
            // Проверяем наличие таблицы site_settings
            if (!$this->tableExists('site_settings')) {
                return false;
            }
            
            // Проверяем флаг установки в настройках
            try {
                $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
                $stmt->execute([self::INSTALL_FLAG_KEY]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && $result['setting_value'] === '1') {
                    return true;
                }
            } catch (PDOException $e) {
                // Если таблица существует, но запрос не выполнился, система не установлена
                return false;
            }
            
            // Проверяем наличие всех обязательных таблиц
            return $this->checkTables();
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
     * Проверка существования конкретной таблицы
     * 
     * @param string $tableName Имя таблицы
     * @return bool
     */
    public function tableExists(string $tableName): bool {
        try {
            $db = $this->getDB();
            if ($db === null) {
                return false;
            }
            
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = ? 
                AND table_name = ?
            ");
            $stmt->execute([DB_NAME, $tableName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return isset($result['count']) && (int)$result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Получение списка отсутствующих таблиц
     * 
     * @return array
     */
    public function getMissingTables(): array {
        $missing = [];
        
        try {
            foreach (self::REQUIRED_TABLES as $table) {
                if (!$this->tableExists($table)) {
                    $missing[] = $table;
                }
            }
        } catch (Exception $e) {
            // В случае ошибки возвращаем все таблицы как отсутствующие
            return self::REQUIRED_TABLES;
        }
        
        return $missing;
    }
    
    /**
     * Установка системы (создание таблиц)
     * 
     * @return array Результат установки ['success' => bool, 'message' => string, 'errors' => array]
     */
    public function install(): array {
        $errors = [];
        
        try {
            $db = $this->getDB();
            if ($db === null) {
                return [
                    'success' => false,
                    'message' => 'Не удалось подключиться к базе данных',
                    'errors' => ['Нет подключения к базе данных']
                ];
            }
            
            // Начинаем транзакцию
            $db->beginTransaction();
            
            try {
                // Создаем таблицы
                $this->createTables($db, $errors);
                
                // Устанавливаем флаг установки
                $this->setInstallFlag($db);
                
                // Коммитим транзакцию
                $db->commit();
                
                return [
                    'success' => empty($errors),
                    'message' => empty($errors) ? 'Система успешно установлена' : 'Установка завершена с ошибками',
                    'errors' => $errors
                ];
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = $e->getMessage();
                return [
                    'success' => false,
                    'message' => 'Ошибка при установке: ' . $e->getMessage(),
                    'errors' => $errors
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Критическая ошибка: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Создание всех таблиц
     * 
     * @param PDO $db Подключение к БД
     * @param array &$errors Массив ошибок
     * @return void
     */
    private function createTables(PDO $db, array &$errors): void {
        $tables = $this->getTableDefinitions();
        
        foreach ($tables as $tableName => $sql) {
            try {
                $db->exec($sql);
            } catch (PDOException $e) {
                $errors[] = "Ошибка создания таблицы {$tableName}: " . $e->getMessage();
            }
        }
    }
    
    /**
     * Установка флага установки системы
     * 
     * @param PDO $db Подключение к БД
     * @return void
     */
    private function setInstallFlag(PDO $db): void {
        try {
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, '1')
                ON DUPLICATE KEY UPDATE setting_value = '1'
            ");
            $stmt->execute([self::INSTALL_FLAG_KEY]);
        } catch (PDOException $e) {
            // Игнорируем ошибку, если таблица еще не создана
        }
    }
    
    /**
     * Получение определений таблиц
     * 
     * @return array
     */
    private function getTableDefinitions(): array {
        return [
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
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

