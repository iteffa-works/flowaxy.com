<?php
/**
 * Конфигурация базы данных
 * Использует улучшенный класс Database из engine/classes/Database.php
 * 
 * @package Config
 * @version 2.0.0
 */

declare(strict_types=1);

// Настройки подключения к базе данных
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.1.26');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'db_flowaxy');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Подключаем улучшенный класс Database
require_once dirname(__DIR__) . '/engine/classes/Database.php';

/**
 * Глобальная функция для получения подключения к БД
 * 
 * @return PDO|null
 */
function getDB(): ?PDO {
    try {
        return Database::getInstance()->getConnection();
    } catch (Exception $e) {
        error_log("getDB error: " . $e->getMessage());
        return null;
    }
}

/**
 * Проверка доступности БД
 * 
 * @return bool
 */
function isDatabaseAvailable(): bool {
    try {
        return Database::getInstance()->isAvailable();
    } catch (Exception $e) {
        error_log("isDatabaseAvailable error: " . $e->getMessage());
        return false;
    }
}
