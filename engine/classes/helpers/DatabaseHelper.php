<?php
/**
 * Хелпер для работы с базой данных
 * Обертка над Database классом
 * 
 * @package Engine\Classes\Helpers
 * @version 1.0.0
 */

declare(strict_types=1);

class DatabaseHelper {
    /**
     * Получение подключения к БД
     * 
     * @param bool $showError Показывать страницу ошибки
     * @return PDO|null
     */
    public static function getConnection(bool $showError = true): ?PDO {
        // Проверяем, что константы БД определены и не пустые
        if (!defined('DB_HOST') || empty(DB_HOST) || !defined('DB_NAME') || empty(DB_NAME)) {
            if ($showError && php_sapi_name() !== 'cli') {
                // Не показываем ошибку БД, если конфигурация не установлена (это нормально для установщика)
                // Просто возвращаем null
            }
            return null;
        }
        
        try {
            if (!class_exists('Database')) {
                // Если Database еще не загружен, пытаемся загрузить
                $dbFile = __DIR__ . '/../data/Database.php';
                if (file_exists($dbFile)) {
                    require_once $dbFile;
                }
            }
            return Database::getInstance()->getConnection();
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Database connection failed', ['error' => $e->getMessage()]);
            } else {
                error_log("getDB error: " . $e->getMessage());
            }
            
            if ($showError && php_sapi_name() !== 'cli') {
                $errorDetails = [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ];
                
                if (function_exists('showDatabaseError')) {
                    showDatabaseError($errorDetails);
                }
                exit;
            }
            
            return null;
        }
    }
    
    /**
     * Проверка доступности БД
     * 
     * @param bool $showError Показывать страницу ошибки при недоступности
     * @return bool
     */
    public static function isAvailable(bool $showError = false): bool {
        // Проверяем, что константы БД определены и не пустые
        if (!defined('DB_HOST') || empty(DB_HOST) || !defined('DB_NAME') || empty(DB_NAME)) {
            return false;
        }
        
        try {
            if (!class_exists('Database')) {
                // Если Database еще не загружен, пытаемся загрузить
                $dbFile = __DIR__ . '/../data/Database.php';
                if (file_exists($dbFile)) {
                    require_once $dbFile;
                }
            }
            $isAvailable = Database::getInstance()->isAvailable();
            
            if (!$isAvailable && $showError && php_sapi_name() !== 'cli') {
                if (function_exists('showDatabaseError')) {
                    showDatabaseError([
                        'host' => DB_HOST,
                        'database' => DB_NAME,
                        'error' => 'База даних недоступна'
                    ]);
                }
                exit;
            }
            
            return $isAvailable;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Database availability check failed', ['error' => $e->getMessage()]);
            } else {
                error_log("isDatabaseAvailable error: " . $e->getMessage());
            }
            
            if ($showError && php_sapi_name() !== 'cli') {
                if (function_exists('showDatabaseError')) {
                    showDatabaseError([
                        'host' => DB_HOST,
                        'database' => DB_NAME,
                        'error' => $e->getMessage(),
                        'code' => $e->getCode()
                    ]);
                }
                exit;
            }
            
            return false;
        }
    }
    
    /**
     * Получение экземпляра Database
     * 
     * @return Database
     */
    public static function getInstance(): Database {
        return Database::getInstance();
    }
    
    /**
     * Проверка существования таблицы в базе данных
     * 
     * @param string $tableName Имя таблицы
     * @return bool
     */
    public static function tableExists(string $tableName): bool {
        try {
            if (!class_exists('Database')) {
                $dbFile = __DIR__ . '/../data/Database.php';
                if (file_exists($dbFile)) {
                    require_once $dbFile;
                }
            }
            
            $db = Database::getInstance();
            $connection = $db->getConnection();
            
            $stmt = $connection->prepare("
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = ? 
                AND table_name = ?
            ");
            $stmt->execute([DB_NAME, $tableName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return isset($result['count']) && (int)$result['count'] > 0;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::getInstance()->logError('Table existence check failed', [
                    'table' => $tableName,
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }
    
    /**
     * Проверка существования всех указанных таблиц
     * 
     * @param array $tables Массив имен таблиц
     * @return array Массив с результатами проверки ['exists' => array, 'missing' => array]
     */
    public static function checkTables(array $tables): array {
        $exists = [];
        $missing = [];
        
        foreach ($tables as $table) {
            if (self::tableExists($table)) {
                $exists[] = $table;
            } else {
                $missing[] = $table;
            }
        }
        
        return [
            'exists' => $exists,
            'missing' => $missing
        ];
    }
}

