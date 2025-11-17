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
}

